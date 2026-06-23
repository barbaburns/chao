FROM python:3.11-slim

ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

RUN apt-get update \
  && apt-get install -y --no-install-recommends poppler-utils curl \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY worker.py /app/worker.py

RUN pip install --no-cache-dir flask requests

EXPOSE 8002

CMD ["python", "worker.py"]
