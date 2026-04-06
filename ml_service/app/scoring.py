"""
Option B routing: zero-shot → one-shot → supervised (XGBoost or LR) by n_labeled.
"""
from __future__ import annotations

import numpy as np
import pandas as pd
from sklearn.metrics import pairwise_distances

from app.artifacts import (
    ArtifactError,
    features_dict_to_dataframe,
    load_feature_columns,
    load_lr_pipeline,
    load_preprocessor,
    load_support_xy,
    load_xgboost_pipeline,
)
from app.config import ONE_SHOT_MAX, ZERO_SHOT_MAX


def _positive_proba(clf, X: pd.DataFrame) -> float:
    """Return probability of positive class (index 1) if available, else first proba."""
    if not hasattr(clf, "predict_proba"):
        p = clf.predict(X)
        return float(np.asarray(p).ravel()[0])
    proba = clf.predict_proba(X)
    arr = np.asarray(proba)
    if arr.ndim == 1:
        return float(arr[0])
    if arr.shape[1] >= 2:
        return float(arr[0, 1])
    return float(arr[0, 0])


def _decision_from_pd(pd_score: float) -> str:
    if pd_score < 0.25:
        return "approve"
    if pd_score < 0.45:
        return "review"
    return "decline"


def route_model(n_labeled: int) -> str:
    if n_labeled <= ZERO_SHOT_MAX:
        return "zero_shot_proto"
    if n_labeled < ONE_SHOT_MAX:
        return "one_shot_proto"
    return "supervised"


def score_payload(
    features: dict,
    n_labeled: int,
    preferred_supervised: str = "xgboost",
    seed: int = 42,
) -> dict:
    rng = np.random.default_rng(seed)
    columns = load_feature_columns()
    pre = load_preprocessor()
    support_X, support_y = load_support_xy()
    # Align support columns to expected feature order
    for c in columns:
        if c not in support_X.columns:
            support_X[c] = np.nan
    support_X = support_X[columns]
    X_df = features_dict_to_dataframe(features, columns)
    X_t = pre.transform(X_df)
    mode = route_model(n_labeled)

    if mode == "zero_shot_proto":
        S_t = pre.transform(support_X)
        centroid = np.mean(S_t, axis=0, keepdims=True)
        dist = float(pairwise_distances(X_t, centroid, metric="euclidean")[0, 0])
        # Softer risk when far from typical support (heuristic placeholder)
        base = float(np.clip(np.mean(support_y.values), 0.0, 1.0))
        pd_score = float(np.clip(base + 0.15 * np.tanh(dist / (np.std(S_t) + 1e-6)), 0.0, 1.0))
        model_used = "zero_shot_proto"

    elif mode == "one_shot_proto":
        S_t = pre.transform(support_X)
        dists = pairwise_distances(X_t, S_t, metric="euclidean").ravel()
        j = int(np.argmin(dists))
        y_nn = float(support_y.iloc[j])
        # Blend with small noise for stability
        pd_score = float(np.clip(y_nn + 0.02 * rng.standard_normal(), 0.0, 1.0))
        model_used = "one_shot_proto"

    else:
        model_used = f"supervised_{preferred_supervised.lower()}"
        if preferred_supervised.lower() in ("lr", "logistic", "logistic_regression"):
            pipe = load_lr_pipeline()
        else:
            pipe = load_xgboost_pipeline()
        pd_score = _positive_proba(pipe, X_df)
        pd_score = float(np.clip(pd_score, 0.0, 1.0))

    return {
        "pd": round(pd_score, 4),
        "decision": _decision_from_pd(pd_score),
        "model_used": model_used,
        "routing": {
            "n_labeled": int(n_labeled),
            "preferred_supervised": preferred_supervised,
        },
        "top_reasons": [
            {
                "feature": "model_score",
                "direction": "+",
                "strength": round(float(pd_score), 4),
            }
        ],
    }


def check_artifacts_ready() -> tuple[bool, str]:
    try:
        load_feature_columns()
        load_preprocessor()
        load_support_xy()
        load_xgboost_pipeline()
        load_lr_pipeline()
        return True, "ok"
    except ArtifactError as e:
        return False, str(e)
    except Exception as e:
        # e.g. XGBoostError: libomp.dylib missing on macOS after pip install xgboost
        msg = (str(e).strip() or type(e).__name__)
        if "libomp" in msg.lower() or "libxgboost" in msg.lower():
            msg += (
                " | macOS: install OpenMP with Homebrew (`brew install libomp`), "
                "then restart the API."
            )
        return False, msg
