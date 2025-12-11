#!/bin/bash

# Helper script to install agent on a remote server

set -e

if [ $# -lt 3 ]; then
    echo "Usage: $0 <server_ip> <agent_token> <api_url>"
    echo "Example: $0 192.168.1.100 abc123def456 http://localhost:8080"
    exit 1
fi

SERVER_IP=$1
AGENT_TOKEN=$2
API_URL=$3

echo "Installing ForgeLite agent on $SERVER_IP..."

# Download and run installer on remote server
ssh root@$SERVER_IP "bash -s" << EOF
export FORGELITE_AGENT_TOKEN="$AGENT_TOKEN"
export FORGELITE_API_URL="$API_URL"
curl -sSL https://raw.githubusercontent.com/yourusername/forgelite/main/agent/install.sh | bash
EOF

echo "Agent installation complete!"

