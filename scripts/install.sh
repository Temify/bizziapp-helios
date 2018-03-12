#!/bin/bash
sudo yum -y remove unixODBC
sudo yum -y install php71-pdo php71-xml php7-pear php71-devel re2c gcc-c++ gcc
sudo su
curl https://packages.microsoft.com/config/rhel/7/prod.repo > /etc/yum.repos.d/mssql-release.repo
exit
sudo yum update
sudo ACCEPT_EULA=Y yum -y install msodbcsql-13.0.1.0-1 mssql-tools-14.0.2.0-1
sudo yum -y install php71-odbc
sudo yum -y install unixODBC-utf16-devel
echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bash_profile
echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc
sudo su
sudo pecl7 install sqlsrv
sudo pecl7 install pdo_sqlsr
exit
sudo setsebool -P httpd_can_network_connect_db 1
sudo service httpd restart
