FROM nvidia/cuda:12.3.2-cudnn9-runtime-ubuntu22.04

ENV DEBIAN_FRONTEND=noninteractive
ENV PYTHONUNBUFFERED=1
ENV PIP_NO_CACHE_DIR=1

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        python3 \
        python3-pip \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .

RUN python3 -m pip install --upgrade pip \
    && python3 -m pip install -r requirements.txt

COPY zoe_gateway.py .

RUN mkdir -p /root/.cache

EXPOSE 8000

CMD ["python3", "zoe_gateway.py"]