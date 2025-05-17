# Run JSON Server using the database file in JSONServer folder
Write-Host "Starting JSON Server on port 4000..."
npx json-server .\JSONServer\db.json --port 4000