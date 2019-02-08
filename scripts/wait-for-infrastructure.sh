#!/bin/bash

exec 6>&-
exec 6<&-

until $(curl --output /dev/null --silent --fail "http://localhost:9200/_cluster/health?wait_for_status=yellow&timeout=10s"); do
    echo "$(date) - can't connect to http://localhost:9200"
    sleep 1
done

exec 6>&-
exec 6<&-