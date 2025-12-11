#!/bin/bash

# Demo script to simulate server registration and deployment
# This script demonstrates the ForgeLite workflow

set -e

API_URL="${FORGELITE_API_URL:-http://localhost:8080}"
EMAIL="${FORGELITE_EMAIL:-demo@example.com}"
PASSWORD="${FORGELITE_PASSWORD:-password}"

echo "ForgeLite Demo Script"
echo "===================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Step 1: Create user (if not exists)
echo -e "${YELLOW}Step 1: Creating user...${NC}"
TOKEN=$(curl -s -X POST "$API_URL/api/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"name\": \"Demo User\",
    \"email\": \"$EMAIL\",
    \"password\": \"$PASSWORD\",
    \"password_confirmation\": \"$PASSWORD\"
  }" | jq -r '.token // .access_token // empty')

if [ -z "$TOKEN" ]; then
    echo "User may already exist, attempting login..."
    TOKEN=$(curl -s -X POST "$API_URL/api/login" \
      -H "Content-Type: application/json" \
      -d "{
        \"email\": \"$EMAIL\",
        \"password\": \"$PASSWORD\"
      }" | jq -r '.token // .access_token // empty')
fi

if [ -z "$TOKEN" ]; then
    echo "Error: Could not authenticate. Please create a user manually."
    exit 1
fi

echo -e "${GREEN}✓ Authenticated${NC}"
echo ""

# Step 2: Create server
echo -e "${YELLOW}Step 2: Creating server...${NC}"
SERVER_RESPONSE=$(curl -s -X POST "$API_URL/api/servers" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Demo Server",
    "hostname": "demo.example.com",
    "ip_address": "192.168.1.100",
    "ssh_user": "root",
    "ssh_port": 22
  }')

SERVER_ID=$(echo $SERVER_RESPONSE | jq -r '.id')
AGENT_TOKEN=$(echo $SERVER_RESPONSE | jq -r '.agent_token')

if [ -z "$SERVER_ID" ] || [ "$SERVER_ID" = "null" ]; then
    echo "Error: Could not create server"
    echo "Response: $SERVER_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✓ Server created (ID: $SERVER_ID)${NC}"
echo "Agent Token: $AGENT_TOKEN"
echo ""

# Step 3: Register agent (simulate)
echo -e "${YELLOW}Step 3: Registering agent...${NC}"
curl -s -X POST "$API_URL/api/agent/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"agent_token\": \"$AGENT_TOKEN\",
    \"hostname\": \"demo.example.com\",
    \"ip_address\": \"192.168.1.100\",
    \"os\": \"Ubuntu 22.04\"
  }" > /dev/null

echo -e "${GREEN}✓ Agent registered${NC}"
echo ""

# Step 4: Report metrics (simulate)
echo -e "${YELLOW}Step 4: Reporting metrics...${NC}"
curl -s -X POST "$API_URL/api/agent/metrics" \
  -H "Content-Type: application/json" \
  -d "{
    \"agent_token\": \"$AGENT_TOKEN\",
    \"cpu_usage\": 25.5,
    \"memory_used_mb\": 2048,
    \"memory_total_mb\": 4096,
    \"disk_used_gb\": 50,
    \"disk_total_gb\": 100,
    \"load_average_1m\": 0.5,
    \"load_average_5m\": 0.6,
    \"load_average_15m\": 0.7
  }" > /dev/null

echo -e "${GREEN}✓ Metrics reported${NC}"
echo ""

# Step 5: Create site
echo -e "${YELLOW}Step 5: Creating site...${NC}"
SITE_RESPONSE=$(curl -s -X POST "$API_URL/api/sites" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"server_id\": $SERVER_ID,
    \"domain\": \"demo.example.com\",
    \"repository_url\": \"https://github.com/laravel/laravel.git\",
    \"repository_branch\": \"main\",
    \"php_version\": \"8.2\",
    \"environment_variables\": {
      \"APP_NAME\": \"Demo App\",
      \"APP_ENV\": \"production\"
    }
  }")

SITE_ID=$(echo $SITE_RESPONSE | jq -r '.id')

if [ -z "$SITE_ID" ] || [ "$SITE_ID" = "null" ]; then
    echo "Error: Could not create site"
    echo "Response: $SITE_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✓ Site created (ID: $SITE_ID)${NC}"
echo ""

# Step 6: Trigger deployment
echo -e "${YELLOW}Step 6: Triggering deployment...${NC}"
DEPLOYMENT_RESPONSE=$(curl -s -X POST "$API_URL/api/deployments" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"site_id\": $SITE_ID
  }")

DEPLOYMENT_ID=$(echo $DEPLOYMENT_RESPONSE | jq -r '.id')

if [ -z "$DEPLOYMENT_ID" ] || [ "$DEPLOYMENT_ID" = "null" ]; then
    echo "Error: Could not create deployment"
    echo "Response: $DEPLOYMENT_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✓ Deployment triggered (ID: $DEPLOYMENT_ID)${NC}"
echo ""

# Summary
echo "Demo Complete!"
echo "=============="
echo "Server ID: $SERVER_ID"
echo "Site ID: $SITE_ID"
echo "Deployment ID: $DEPLOYMENT_ID"
echo ""
echo "View in dashboard: $API_URL/dashboard"
echo ""

