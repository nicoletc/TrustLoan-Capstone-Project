"""Load joblib / parquet / JSON artifacts from exports/."""
from __future__ import annotations

import json
from functools import lru_cache
from pathlib import Path
from typing import Any

import joblib
import pandas as pd

from app.config import ARTIFACTS


class ArtifactError(RuntimeError):
    pass


def _require(path: Path) -> Path:
    if not path.is_file():
        raise ArtifactError(f"Missing artifact: {path}")
    return path


@lru_cache(maxsize=1)
def load_feature_columns() -> list[str]:
    p = _require(ARTIFACTS["feature_columns"])
    data: Any = json.loads(p.read_text(encoding="utf-8"))
    if isinstance(data, dict) and "columns" in data:
        return list(data["columns"])
    if isinstance(data, list):
        return [str(x) for x in data]
    raise ArtifactError("feature_columns.json must be a list of names or {\"columns\": [...]}")


@lru_cache(maxsize=1)
def load_preprocessor():
    return joblib.load(_require(ARTIFACTS["preprocessor"]))


@lru_cache(maxsize=1)
def load_support_xy():
    sx = pd.read_parquet(_require(ARTIFACTS["support_X"]))
    sy = pd.read_parquet(_require(ARTIFACTS["support_y"]))
    if len(sy.columns) == 1:
        y = sy.iloc[:, 0].astype(float)
    else:
        y = sy.iloc[:, 0].astype(float)
    return sx, y


@lru_cache(maxsize=1)
def load_xgboost_pipeline():
    return joblib.load(_require(ARTIFACTS["xgboost_pipeline"]))


@lru_cache(maxsize=1)
def load_lr_pipeline():
    return joblib.load(_require(ARTIFACTS["lr_pipeline"]))


def features_dict_to_dataframe(features: dict, columns: list[str]) -> pd.DataFrame:
    row = {}
    for c in columns:
        row[c] = features.get(c)
    return pd.DataFrame([row])
