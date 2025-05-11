#!/bin/bash
# This script executes the init.sql file against the PostgreSQL database in the Docker container.

SQL_FILE=$1

# Check if an SQL file argument is provided
if [ -z "$SQL_FILE" ]; then
  echo "Usage: $0 <sql_file>"
fi

# Check if the SQL file exists
if [ ! -f "$SQL_FILE" ]; then
  echo "File $SQL_FILE not found"
fi

# Option 1: Pipe the init.sql to psql
cat ./init.sql | docker compose exec -T postgres psql -U w1 -d w1

# Ensure PGPASSWORD is set if psql prompts for it, though often not needed if .pgpass is configured or trust auth.
# For explicit password usage (if needed, replace 'pass123' with the actual password if it differs):
# cat ./init.sql | PGPASSWORD=pass123 docker compose exec -T postgres psql -h localhost -p 5433 -U w1 -d w1

echo "Database initialization script (init.sql) executed."

# Check the exit status of the command
if [ $? -eq 0 ]; then
  echo "Database updated successfully"
else
  echo "Failed to update database"
fi