FROM node:22-bookworm

WORKDIR /app

COPY package.json package-lock.json* ./
RUN if [ -f package.json ]; then npm install || true; fi

COPY . .

EXPOSE 43123

CMD ["npm", "run", "dev", "--", "--hostname", "0.0.0.0", "--port", "43123"]
