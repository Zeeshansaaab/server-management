#!/bin/bash

# ForgeLite Agent Installer
# This script installs the agent on a server to communicate with the ForgeLite platform

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
AGENT_DIR="/opt/forgelite-agent"
SERVICE_FILE="/etc/systemd/system/forgelite-agent.service"
API_URL="${FORGELITE_API_URL:-http://localhost:8080}"
AGENT_TOKEN="${FORGELITE_AGENT_TOKEN}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root${NC}"
    exit 1
fi

# Check if agent token is provided
if [ -z "$AGENT_TOKEN" ]; then
    echo -e "${RED}Error: FORGELITE_AGENT_TOKEN environment variable is required${NC}"
    echo "Usage: FORGELITE_AGENT_TOKEN=your_token FORGELITE_API_URL=https://your-platform.com bash -s < install.sh"
    exit 1
fi

echo -e "${GREEN}Installing ForgeLite Agent...${NC}"

# Create agent directory
mkdir -p "$AGENT_DIR"
cd "$AGENT_DIR"

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    VER=$VERSION_ID
else
    echo -e "${RED}Cannot detect OS${NC}"
    exit 1
fi

# Install dependencies
echo -e "${YELLOW}Installing dependencies...${NC}"
if [ "$OS" = "ubuntu" ] || [ "$OS" = "debian" ]; then
    apt-get update
    apt-get install -y curl jq bc
elif [ "$OS" = "centos" ] || [ "$OS" = "rhel" ]; then
    yum install -y curl jq bc
else
    echo -e "${YELLOW}Warning: Unknown OS. Please install curl, jq, and bc manually${NC}"
fi

# Create agent script
cat > "$AGENT_DIR/agent.sh" << 'AGENT_SCRIPT'
#!/bin/bash

# ForgeLite Agent
# Reports metrics and receives commands from the ForgeLite platform

set -e

AGENT_DIR="/opt/forgelite-agent"
CONFIG_FILE="$AGENT_DIR/config"
API_URL=""
AGENT_TOKEN=""

# Load config
if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
fi

# Get system metrics
get_metrics() {
    # CPU usage
    CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}')

    # Memory
    MEMORY_TOTAL=$(free -m | awk 'NR==2{printf "%.0f", $2}')
    MEMORY_USED=$(free -m | awk 'NR==2{printf "%.0f", $3}')

    # Disk
    DISK_TOTAL=$(df -BG / | awk 'NR==2 {print $2}' | sed 's/G//')
    DISK_USED=$(df -BG / | awk 'NR==2 {print $3}' | sed 's/G//')

    # Load average
    LOAD_1M=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
    LOAD_5M=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $2}' | sed 's/,//')
    LOAD_15M=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $3}')

    # Hostname and IP
    HOSTNAME=$(hostname)
    IP_ADDRESS=$(hostname -I | awk '{print $1}')

    # OS info
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS="$ID $VERSION_ID"
    else
        OS="unknown"
    fi

    # Send metrics
    curl -s -X POST "$API_URL/api/agent/metrics" \
        -H "Content-Type: application/json" \
        -d "{
            \"agent_token\": \"$AGENT_TOKEN\",
            \"cpu_usage\": $CPU_USAGE,
            \"memory_used_mb\": $MEMORY_USED,
            \"memory_total_mb\": $MEMORY_TOTAL,
            \"disk_used_gb\": $DISK_USED,
            \"disk_total_gb\": $DISK_TOTAL,
            \"load_average_1m\": $LOAD_1M,
            \"load_average_5m\": $LOAD_5M,
            \"load_average_15m\": $LOAD_15M
        }" > /dev/null || true

    # Register on first run
    if [ ! -f "$AGENT_DIR/registered" ]; then
        curl -s -X POST "$API_URL/api/agent/register" \
            -H "Content-Type: application/json" \
            -d "{
                \"agent_token\": \"$AGENT_TOKEN\",
                \"hostname\": \"$HOSTNAME\",
                \"ip_address\": \"$IP_ADDRESS\",
                \"os\": \"$OS\"
            }" > /dev/null && touch "$AGENT_DIR/registered" || true
    fi
}

# Main loop
while true; do
    get_metrics
    sleep 60
done
AGENT_SCRIPT

chmod +x "$AGENT_DIR/agent.sh"

# Create config file
cat > "$AGENT_DIR/config" << CONFIG
API_URL="$API_URL"
AGENT_TOKEN="$AGENT_TOKEN"
CONFIG

# Create systemd service
cat > "$SERVICE_FILE" << SERVICE
[Unit]
Description=ForgeLite Agent
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=$AGENT_DIR
ExecStart=$AGENT_DIR/agent.sh
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
SERVICE

# Enable and start service
systemctl daemon-reload
systemctl enable forgelite-agent
systemctl start forgelite-agent

echo -e "${GREEN}ForgeLite Agent installed successfully!${NC}"
echo -e "${GREEN}Service status:${NC}"
systemctl status forgelite-agent --no-pager -l

