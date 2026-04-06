from __future__ import annotations

from typing import Any, Literal

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

from app.artifacts import ArtifactError
from app.scoring import check_artifacts_ready, score_payload

app = FastAPI(
    title="TrustLoan ML Scoring (Option B)",
    description="Scarcity-aware routing: zero-shot → one-shot → supervised.",
    version="1.0.0",
)


class ScoreRequest(BaseModel):
    features: dict[str, Any]
    n_labeled: int = Field(..., ge=0, description="How many labeled examples are available in deployment.")
    preferred_supervised: Literal["xgboost", "lr"] = "xgboost"
    seed: int = 42


class ScoreResponse(BaseModel):
    pd: float
    decision: str
    model_used: str
    routing: dict
    top_reasons: list


@app.get("/health")
def health():
    ok, msg = check_artifacts_ready()
    return {"status": "ok" if ok else "degraded", "artifacts": msg}


@app.post("/score", response_model=ScoreResponse)
def score(req: ScoreRequest):
    try:
        out = score_payload(
            features=req.features,
            n_labeled=req.n_labeled,
            preferred_supervised=req.preferred_supervised,
            seed=req.seed,
        )
        return out
    except ArtifactError as e:
        raise HTTPException(status_code=503, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
