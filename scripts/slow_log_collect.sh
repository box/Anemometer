#/usr/bin/env bash

# anemometer collection script to gather and digest slow query logs
#
# basic usage would be to add this to cron like this:
# */5 * * * * slow_log_collect.sh --interval 15 --history-db-host anemometer-db.example.com
#
# This will have to run as a user which has write privileges to the mysql slow log
#
# Additionally there are two sets of permissions to worry about:  The local mysql instance, and the remote digest storage instance
# These are handled through defaults files, just create a file in the: my.cnf format such as:
# [client]
# user=
# password=
#
# use --defaults-file for permissions to the local mysql instance
# and use --history-defaults-file for permissions to the remote digest storage instance
#
#

socket= defaults_file= mysqlopts=
digest='/usr/local/bin/pt-query-digest'

history_db_host='localhost'
history_db_port=3306
history_db_name='anemometer'
history_defaults_file='~/.my.cnf'

help () {
	cat <<EOF

Usage: $0 --interval <seconds>

Options:
    --socket -S              The mysql socket to use
    --defaults-file          The defaults file to use for the client

    --history-db-host        Hostname of anemometer database server
    --history-db-port        Port of anemometer database server
    --history-db-name        Database name of anemometer database server (Default anemometer)
    --history-defaults-file  Defaults file to pass to pt-query-digest for connecting to the remote anemometer database
EOF
}

while test $# -gt 0
do
    case $1 in
    --socket|-S)
        socket=$2
        shift
        ;;
    --defaults-file|-f)
        defaults_file=$2
        shift
        ;;
	--pt-query-digest|-d)
	    digest=$2
	    shift
	    ;;
	--help)
	    help
	    exit 0
	    ;;
	--history-db-host)
	    history_db_host=$2
	    shift
	    ;;
    --history-db-port)
        history_db_port=$2
    	shift
    	;;
	--history-db-name)
	    history_db_name=$2
	    shift
	    ;;
	--history-defaults-file)
	    history_defaults_file=$2
	    shift
	    ;;
        *)
            echo >&2 "Invalid argument: $1"
            ;;
    esac
    shift
done

if [ ! -e "${digest}" ];
then
	echo "Error: cannot find digest script at: ${digest}"
	exit 1
fi

if [ ! -z "${defaults_file}" ];
then
	mysqlopts="--defaults-file=${defaults_file}"
fi

# path to the slow query log
LOG=$( mysql $mysqlopts -e " show global variables like 'slow_query_log_file'" -B  | tail -n1 | awk '{ print $2 }' )
if [ $? -ne 0 ];
then
	echo "Error getting slow log file location"
	exit 1
fi

mv "$LOG" /tmp/tmp_slow_log

mysql $mysqlopts -e "FLUSH SLOW LOGS"

if [ ! -z "${history_defaults_file}" ];
then
	pass_opt="--defaults-file=${history_defaults_file}"
fi
"${digest}" $pass_opt \
  --review h="${history_db_host}",D="$history_db_name",t=global_query_review \
  --history h="${history_db_host}",D="$history_db_name",t=global_query_review_history \
  --no-report --limit=0\% \
  --filter="\$event->{Bytes} = length(\$event->{arg})" \
  "/tmp/tmp_slow_log"

rm /tmp/tmp_slow_log
