PYTHON ?= python3
VENV ?= .venv

.PHONY: setup run health

setup:
	$(PYTHON) -m venv $(VENV)
	$(VENV)/bin/pip install -U pip
	$(VENV)/bin/pip install -r requirements.txt

run:
	$(VENV)/bin/uvicorn app.main:app --reload --host 127.0.0.1 --port 8000

health:
	curl -s http://127.0.0.1:8000/health
