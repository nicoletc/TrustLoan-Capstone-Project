import os
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
EXPORTS_DIR = Path(os.environ.get("TRUSTLOAN_ML_EXPORTS", BASE_DIR / "exports"))

# If n_labeled is 0 → zero-shot; if 1 <= n_labeled < ONE_SHOT_MAX → one-shot; else supervised.
ZERO_SHOT_MAX = int(os.environ.get("TRUSTLOAN_N_ZERO_SHOT_MAX", "0"))
ONE_SHOT_MAX = int(os.environ.get("TRUSTLOAN_N_ONE_SHOT_MAX", "50"))

ARTIFACTS = {
    "preprocessor": EXPORTS_DIR / "preprocessor.pkl",
    "xgboost_pipeline": EXPORTS_DIR / "xgboost_pipeline.pkl",
    "lr_pipeline": EXPORTS_DIR / "lr_pipeline.pkl",
    "support_X": EXPORTS_DIR / "support_X.parquet",
    "support_y": EXPORTS_DIR / "support_y.parquet",
    "feature_columns": EXPORTS_DIR / "feature_columns.json",
}
