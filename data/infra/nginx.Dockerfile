FROM nginx:1.11.6-alpine
MAINTAINER Alejandro Celaya <alejandro@alejandrocelaya.com>

# Delete default nginx vhost
RUN rm /etc/nginx/conf.d/default.conf
