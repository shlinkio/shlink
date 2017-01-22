FROM mysql:5.7
MAINTAINER Alejandro Celaya <alejandro@alejandrocelaya.com>

# Enable remote access (default is localhost only, we change this
# otherwise our database would not be reachable from outside the container)
RUN sed -i -e"s/^bind-address\s*=\s*127.0.0.1/bind-address = 0.0.0.0/" /etc/mysql/my.cnf
