#!/bin/bash
wget https://github.com/EreMaijala/unixODBC/raw/master/RPMS/unixODBC-2.3.4-1.el7.x86_64.rpm
wget https://github.com/EreMaijala/unixODBC/raw/master/RPMS/unixODBC-devel-2.3.4-1.el7.x86_64.rpm
sudo yum -y remove unixODBC
sudo rpm -i unixODBC-2.3.4-1.el7.x86_64.rpm
sudo rpm -i unixODBC-devel-2.3.4-1.el7.x86_64.rpm
sudo yum -y install php71-odbc
sudo yum install php71-pdo php71-xml php7-pear php71-devel re2c gcc-c++ gcc
sudo su
curl https://packages.microsoft.com/config/rhel/7/prod.repo > /etc/yum.repos.d/mssql-release.repo
exit
sudo yum -y update
sudo yum -y remove unixODBC-utf16-devel
sudo ACCEPT_EULA=Y yum -y install msodbcsql mssql-tools
echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bash_profile
echo 'export PATH="$PATH:/opt/mssql-tools/bin"' >> ~/.bashrc
sudo su
sudo pecl7 install sqlsrv
sudo pecl7 install pdo_sqlsr
exit
sudo setsebool -P httpd_can_network_connect_db 1
sudo service httpd restart