# Agent Installation Guide

The ForgeLite agent is a lightweight script that runs on each managed server to report metrics and receive commands.

## Prerequisites

- Root or sudo access on the server
- Ubuntu 20.04+ or Debian 11+ (other Linux distributions may work)
- Internet connectivity to reach the ForgeLite platform

## Installation Methods

### Method 1: One-Line Install (Recommended)

```bash
FORGELITE_AGENT_TOKEN=your_token_here FORGELITE_API_URL=https://your-platform.com bash -s < agent/install.sh
```

### Method 2: Manual Download and Install

1. **Download the installer**:
   ```bash
   curl -O https://raw.githubusercontent.com/yourusername/forgelite/main/agent/install.sh
   chmod +x install.sh
   ```

2. **Run with environment variables**:
   ```bash
   export FORGELITE_AGENT_TOKEN=your_token_here
   export FORGELITE_API_URL=https://your-platform.com
   ./install.sh
   ```

### Method 3: Using the Helper Script

From your local machine:
```bash
./scripts/install_agent.sh <server_ip> <agent_token> <api_url>
```

## Getting Your Agent Token

1. Log into ForgeLite dashboard
2. Go to Servers → Add Server
3. Enter server details (name, IP address)
4. Copy the generated agent token
5. Use this token in the installation command

## What the Installer Does

1. **Creates agent directory**: `/opt/forgelite-agent`
2. **Installs dependencies**: curl, jq, bc
3. **Creates agent script**: `/opt/forgelite-agent/agent.sh`
4. **Creates config file**: `/opt/forgelite-agent/config`
5. **Creates systemd service**: `/etc/systemd/system/forgelite-agent.service`
6. **Starts and enables the service**

## Verification

After installation, verify the agent is running:

```bash
# Check service status
systemctl status forgelite-agent

# View logs
journalctl -u forgelite-agent -f

# Check if agent registered
# The agent should appear as "online" in the ForgeLite dashboard
```

## Agent Configuration

The agent configuration is stored in `/opt/forgelite-agent/config`:

```bash
API_URL=https://your-platform.com
AGENT_TOKEN=your_token_here
```

To update configuration:
1. Edit `/opt/forgelite-agent/config`
2. Restart the service: `systemctl restart forgelite-agent`

## Troubleshooting

### Agent Not Appearing Online

1. **Check service status**:
   ```bash
   systemctl status forgelite-agent
   ```

2. **Check logs**:
   ```bash
   journalctl -u forgelite-agent -n 50
   ```

3. **Verify network connectivity**:
   ```bash
   curl -v https://your-platform.com/api/agent/register
   ```

4. **Verify token**:
   - Check token in config file
   - Ensure token matches the one in ForgeLite dashboard

### Agent Not Reporting Metrics

1. **Check if agent can reach API**:
   ```bash
   curl -X POST https://your-platform.com/api/agent/metrics \
     -H "Content-Type: application/json" \
     -d '{"agent_token":"your_token"}'
   ```

2. **Check system resources**:
   ```bash
   # The agent needs basic system tools
   which curl jq bc
   ```

### Service Won't Start

1. **Check permissions**:
   ```bash
   ls -la /opt/forgelite-agent
   chmod +x /opt/forgelite-agent/agent.sh
   ```

2. **Check systemd logs**:
   ```bash
   journalctl -xe
   ```

## Uninstallation

To remove the agent:

```bash
systemctl stop forgelite-agent
systemctl disable forgelite-agent
rm /etc/systemd/system/forgelite-agent.service
rm -rf /opt/forgelite-agent
systemctl daemon-reload
```

## Security Considerations

- The agent token is stored in plain text in the config file (root-only readable)
- The agent runs as root to collect system metrics
- Network communication uses HTTPS (ensure your platform uses SSL)
- Consider firewall rules to restrict agent communication

## Manual Agent Script

If you prefer to run the agent manually (not recommended for production):

```bash
cd /opt/forgelite-agent
./agent.sh
```

The agent will run in the foreground and report metrics every 60 seconds.

