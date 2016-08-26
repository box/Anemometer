#!/usr/bin/env python
import os
import tempfile
import time
from subprocess import Popen, PIPE


class DatabaseException(Exception):
    """Exception class for database errors"""


def wait_for_mysql(host='localhost', port=3306, user='root', password='', timeout=300):
    """
    Wait until MySQL becomes available or timeout expires

    :param host: MySQL host
    :param port: MySQL port
    :param user: MySQL user
    :param password: MySQL password
    :param timeout: wait up to this number of seconds
    :return: None
    :raise: Exception
    """
    time_b = time.time() + timeout
    cmd = ['mysql',
           '-NB',
           '-h', host,
           '-P', str(port),
           '-u', user]
    if password:
        cmd.append('-p%s' % password)
    cmd.append('-e')
    cmd.append('SELECT 1')

    while time.time() < time_b:

        process = Popen(cmd, stdout=PIPE, stderr=PIPE)
        cout, cerr = process.communicate()
        if process.returncode:
            if cerr:
                print(cerr)
            time.sleep(1)
        else:
            return

    raise DatabaseException('Could not connect to MySQL after %d seconds' % timeout)


def mysql_load_file(file_path, host='root', port=3306, user='root', password='', db=None):
    """
    Execute SQL dump

    :param file_path: Path to the SQL dump
    :param host: MySQL host
    :param port: MySQL port
    :param user: MySQL user
    :param password: MySQL password
    :param db: MySQL database
    :return: None
    """
    cmd = ['mysql',
           '-h', host,
           '-P', str(port),
           '-u', user
           ]

    if password:
        cmd.append('-p%s' % password)

    if db:
        cmd.append(db)

    try:
        process = Popen(cmd, stdout=PIPE, stderr=PIPE, stdin=open(file_path))
        cout, cerr = process.communicate()
        if process.returncode:
            raise DatabaseException('MySQL Error: %s' % cerr)
    except IOError as err:
        raise DatabaseException(err)


def main():

    try:
        mysql_host = os.environ['ANEMOMETER_MYSQL_HOST']
    except KeyError:
        mysql_host = 'localhost'

    try:
        mysql_port = os.environ['ANEMOMETER_MYSQL_PORT']
    except KeyError:
        mysql_port = 3306

    try:
        mysql_user = os.environ['ANEMOMETER_MYSQL_USER']
    except KeyError:
        mysql_user = 'root'

    try:
        mysql_password = os.environ['ANEMOMETER_MYSQL_PASSWORD']
    except KeyError:
        mysql_password = ''

    try:
        mysql_db = os.environ['ANEMOMETER_MYSQL_DB']
    except KeyError:
        mysql_db = 'slow_query_log'

    try:
        wait_for_mysql(host=mysql_host,
                       port=mysql_port,
                       user=mysql_user,
                       password=mysql_password)

        with tempfile.NamedTemporaryFile() as fp:
            fp.write('CREATE DATABASE IF NOT EXISTS `%s`' % mysql_db)
            fp.flush()
            mysql_load_file(fp.name,
                            host=mysql_host,
                            port=mysql_port,
                            user=mysql_user,
                            password=mysql_password)

        mysql_load_file('/var/www/html/install.sql',
                        host=mysql_host,
                        port=mysql_port,
                        user=mysql_user,
                        password=mysql_password,
                        db=mysql_db)

        mysql_load_file('/var/www/html/mysql56-install.sql',
                        host=mysql_host,
                        port=mysql_port,
                        user=mysql_user,
                        password=mysql_password,
                        db=mysql_db)

    except DatabaseException as err:
        exit(err)

    p = Popen(['httpd', '-DFOREGROUND'])
    p.wait()


if __name__ == '__main__':
    try:
        main()
    except KeyboardInterrupt:
        exit(0)
