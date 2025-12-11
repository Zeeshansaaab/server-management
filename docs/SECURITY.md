# Security Guide

This guide covers security best practices for ForgeLite.

## Overview

ForgeLite handles sensitive data including:
- SSH private keys
- Database credentials
- Environment variables
- Agent tokens

All sensitive data is encrypted using Laravel's encryption.

## Encryption

### How It Works

ForgeLite uses Laravel's built-in encryption:
- Encryption key stored in `APP_KEY` environment variable
- Uses AES-256-CBC encryption
- Keys encrypted at rest in database

### Encrypted Data

- SSH private keys
- Database passwords
- Environment variables (site `.env` files)
- Two-factor authentication secrets

## SSH Key Management

### Option 1: Agent-Based (Recommended)

1. Create server in ForgeLite
2. Copy agent token
3. Install agent on server (no SSH key needed)
4. Agent uses token for authentication

**Pros**: No SSH keys stored in platform
**Cons**: Requires agent installation

### Option 2: SSH Key-Based

1. Generate SSH key pair:
   ```bash
   ssh-keygen -t ed25519 -C "forgelite"
   ```

2. Add public key to server:
   ```bash
   ssh-copy-id -i ~/.ssh/id_ed25519.pub user@server
   ```

3. Provide private key to ForgeLite (encrypted)

**Pros**: Standard SSH authentication
**Cons**: Private key stored (encrypted) in database

### Best Practices

- Use agent-based authentication when possible
- Rotate SSH keys regularly
- Use separate keys for each server
- Never commit SSH keys to version control
- Use strong passphrases for SSH keys

## Agent Token Security

### Token Generation

- Tokens are 64-character random strings
- Generated automatically when server is created
- Unique per server

### Token Storage

- Stored in database (not encrypted, but unique)
- Stored in agent config file (root-only readable)
- Should be rotated if compromised

### Token Rotation

1. Generate new token in ForgeLite
2. Update agent config on server
3. Restart agent service

## Database Security

### Credential Storage

- Database passwords encrypted in database
- Credentials never logged
- Access via encrypted attributes

### Database Access

- Databases created with minimal privileges
- Users scoped to specific databases
- Strong random passwords generated

### Best Practices

- Use strong passwords (auto-generated)
- Limit database user privileges
- Regularly rotate database passwords
- Use SSL for database connections (if remote)

## Environment Variables

### Storage

- Environment variables encrypted as JSON
- Decrypted only when deploying
- Written to `.env` file on server

### Access Control

- Only site owner can view/edit
- Variables not exposed in API responses
- Logged only if explicitly enabled

## Network Security

### HTTPS

- Always use HTTPS in production
- Let's Encrypt certificates recommended
- Force HTTPS redirects

### Firewall

Configure firewall on managed servers:

```bash
# Allow SSH
ufw allow 22/tcp

# Allow HTTP/HTTPS
ufw allow 80/tcp
ufw allow 443/tcp

# Deny all other traffic
ufw default deny incoming
ufw enable
```

### Agent Communication

- Agent communicates over HTTPS
- Token-based authentication
- No persistent connections
- Firewall should allow outbound HTTPS

## Application Security

### Authentication

- Use strong passwords
- Enable two-factor authentication (when implemented)
- Regular password rotation

### Authorization

- Policy-based authorization
- Users can only access their own servers
- API tokens for programmatic access

### Session Security

- Secure session cookies
- CSRF protection enabled
- Session timeout configured

## Server Security

### System Updates

Keep managed servers updated:

```bash
apt update && apt upgrade -y
```

### User Permissions

- Sites run as dedicated system users
- Minimal privileges
- No sudo access for site users

### File Permissions

- Proper ownership (www-data or site user)
- Restrictive file permissions
- Protect sensitive files

## Monitoring and Logging

### Activity Logs

- All actions logged
- User actions tracked
- Deployment history maintained

### Security Monitoring

- Monitor failed login attempts
- Track unusual activity
- Alert on security events

## Backup Security

### Backup Storage

- Backups stored on server (encrypted)
- Optional remote storage (S3, etc.)
- Encrypted in transit and at rest

### Backup Access

- Backups accessible only to site owner
- Download requires authentication
- Automatic cleanup of old backups

## Incident Response

### If SSH Key Compromised

1. Revoke key in ForgeLite
2. Generate new key pair
3. Update server access
4. Rotate agent token

### If Agent Token Compromised

1. Generate new token
2. Update agent config
3. Restart agent
4. Monitor for unauthorized access

### If Database Credentials Compromised

1. Change database password
2. Update ForgeLite with new credentials
3. Update application `.env`
4. Review database access logs

## Compliance

### Data Protection

- Encrypt sensitive data at rest
- Encrypt data in transit (HTTPS)
- Regular security audits
- Access logging

### Privacy

- No data shared with third parties
- User data stored securely
- Right to deletion

## Security Checklist

- [ ] Use HTTPS in production
- [ ] Enable firewall on servers
- [ ] Use agent-based authentication when possible
- [ ] Rotate SSH keys regularly
- [ ] Use strong, unique passwords
- [ ] Enable two-factor authentication
- [ ] Regular security updates
- [ ] Monitor activity logs
- [ ] Backup encryption enabled
- [ ] Access control policies configured

## Reporting Security Issues

If you discover a security vulnerability, please:
1. Do not open a public issue
2. Email security@your-domain.com
3. Include details and steps to reproduce
4. Allow time for fix before disclosure

