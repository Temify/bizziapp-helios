#!/bin/bash
sudo su
yum -y remove unixODBC
yum -y install php71-pdo php71-xml php7-pear php71-devel re2c gcc-c++ gcc
curl https://packages.microsoft.com/config/rhel/7/prod.repo > /etc/yum.repos.d/mssql-release.repo
yum update
ACCEPT_EULA=Y yum -y install msodbcsql-13.0.1.0-1 mssql-tools-14.0.2.0-1
yum -y install php71-odbc
yum -y install unixODBC-utf16-devel
pecl7 install sqlsrv
pecl7 install pdo_sqlsrv
service httpd restart
exit
