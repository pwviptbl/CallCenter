FROM nginx:alpine

# Copiar configuração customizada do Nginx
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Expor porta
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
