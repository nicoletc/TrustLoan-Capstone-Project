Place your trained artifacts in this folder:

  preprocessor.pkl
  xgboost_pipeline.pkl
  lr_pipeline.pkl
  support_X.parquet
  support_y.parquet
  feature_columns.json

Set TRUSTLOAN_ML_EXPORTS to override this path.

Optional env tuning:
  TRUSTLOAN_N_ZERO_SHOT_MAX (default 0)   — n_labeled at or below uses zero-shot
  TRUSTLOAN_N_ONE_SHOT_MAX (default 50) — n_labeled below this uses one-shot (above zero band)

macOS (Apple Silicon or Intel): if GET /health or scoring fails with
  XGBoost Library could not be loaded / libomp.dylib
install OpenMP once, then restart uvicorn:
  brew install libomp
