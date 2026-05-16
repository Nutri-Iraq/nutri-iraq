FROM node:20-alpine
WORKDIR /app
COPY frontend/package.json ./
RUN npm install --legacy-peer-deps
COPY frontend/ .
RUN npm run build
EXPOSE 3000
CMD ["node", "server.js"]
