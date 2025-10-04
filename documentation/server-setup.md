# Nextcloud Server Documentation

## Development Setup

### Initial Setup
```bash
# Clone the repository and initialize submodules
git submodule update --init

# Clone default apps into the 'apps' subfolder if missing
git clone <app-repo> apps/<app-name>
```

### Commit Guidelines
```bash
# Use sign-off for commits
git commit -sm "Your commit message"
```

## Testing Infrastructure

### S3 Primary Storage Testing

#### Install and Run FakeS3
```bash
# Install the FakeS3 gem for local S3 testing
sudo gem install fakes3

# Create directory and start FakeS3 server
mkdir /tmp/s3
fakes3 -r /tmp/s3 -p 4567
```

#### S3 Configuration (config.php)

**Single Bucket Configuration:**
```php
'objectstore' => [
    'class' => 'OC\\Files\\ObjectStore\\S3',
    'arguments' => [
        'bucket' => 'abc',
        'key' => '123',
        'secret' => 'abc',
        'hostname' => 'localhost',
        'port' => '4567',
        'use_ssl' => false,
        'use_path_style' => 'true',
    ],
],
```

**Multi-Bucket Configuration:**
```php
'objectstore_multibucket' => [
    'class' => 'OC\\Files\\ObjectStore\\S3',
    'arguments' => [
        'bucket' => 'abc',
        'num_buckets' => 64,
        'key' => '123',
        'secret' => 'abc',
        'hostname' => 'localhost',
        'port' => '4567',
        'use_ssl' => false,
        'use_path_style' => 'true',
    ],
],
```

### LDAP Testing

#### Setup LDAP Server with Docker
```bash
# Clone administration repository
git clone https://github.com/owncloud/administration

# Start LDAP server
administration/ldap-testing/start.sh
```

#### Populate LDAP with Test Data
```bash
cd administration/ldap-testing/

# Create batch of users
php batchCreateUsers.php

# Create users in groups (requires editing config.php first)
gedit config.php
php batchCreateUsersInGroups.php
```

#### LDAP Configuration Parameters
- **Server:** localhost
- **Port:** autodetected
- **User DN:** cn=admin,dc=owncloud,dc=com
- **Password:** admin
- **Base DN:** dc=owncloud,dc=com
- **User Filter:** inetOrgPerson
- **Login Filter:** LDAP Username
- **User Display Name Field:** displayName
- **UUID Attribute for Users:** uid
- **Group-Member association:** memberUid or member

#### Running LDAP Integration Tests
```bash
# From the setup-scripts directory
# Requires root privileges
./run-test.sh [phpscript]
```

Example output:
```bash
$ sudo ./run-test.sh lib/IntegrationTestAccessGroupsMatchFilter.php 
71cbe88a4993e67066714d71c1cecc5ef26a54911a208103cb6294f90459e574
c74dc0155db4efa7a0515d419528a8727bbc7596601cf25b0df05e348bd74895
CONTAINER ID        IMAGE                       COMMAND             CREATED             STATUS                  PORTS                           NAMES
c74dc0155db4        osixia/phpldapadmin:0.5.1   "/sbin/my_init"     1 seconds ago       Up Less than a second   80/tcp, 0.0.0.0:8443->443/tcp   docker-phpldapadmin   
71cbe88a4993        nickstenning/slapd:latest   "/sbin/my_init"     1 seconds ago       Up Less than a second   127.0.0.1:7770->389/tcp         docker-slapd          

LDAP server now available under 127.0.0.1:7770 (internal IP is 172.17.0.78)
phpldapadmin now available under https://127.0.0.1:8443

created user : Alice Ealic
created group : RedGroup
created group : BlueGroup
created group : GreenGroup
created group : PurpleGroup
running case1 
running case2 
Tests succeeded
Stopping and resetting containers
```

### External Storage Testing

#### Automated Testing
```bash
# Run all external storage tests
./autotest-external.sh

# Specify database type
./autotest-external.sh sqlite

# Test specific provider
./autotest-external.sh sqlite webdav-ownCloud

# Run common tests
./autotest-external.sh sqlite common-tests
```

#### Manual Testing for Debugging
```bash
# 1. Start the external storage provider
env/start-BACKEND-NAME.sh

# 2. Run unit tests (repeatable for debugging)
phpunit --configuration ../../../tests/phpunit-autotest-external.xml backends/BACKEND.php

# 3. Clean up
env/stop-BACKEND-NAME.sh
```

## Code Signing with GPG

### Setup GPG for Commit Signing

1. **Import your PGP key:**
```bash
gpg --import /path/to/yourkey.pub-sec.asc
```

2. **Configure Git user information:**
```bash
git config --global user.name "Nextcloud Packager (ncpkger)"
git config --global user.email nextcloudpackager@nextcloud.com
```

3. **Configure GPG program for Windows (Gpg4win):**
```bash
# For Gpg4win v2
git config --global gpg.program "c:/Program Files (x86)/GNU/GnuPG/gpg2.exe"

# For Gpg4win v3
git config --global gpg.program "c:/Program Files (x86)/GnuPG/bin/gpg.exe"
```

4. **Enable automatic commit signing:**
```bash
git config --global commit.gpgsign true
```

5. **Set your signing key:**
```bash
git config --global user.signingkey YOUR_KEY_ID
```

6. **Verify commit signature:**
```bash
git log COMMIT_HASH --show-signature -1
```

## Development Tools & Resources

### Testing Tools
- **BrowserStack:** Cross-browser testing
- **WAVE:** Accessibility testing
- **Lighthouse:** Performance and accessibility testing

### GitHub Bot Commands
- `/update-3rdparty` - Updates the 3rd party submodule to the last commit of the 3rd party branch matching the PR target

### Client Installation
Users can download Nextcloud clients from: https://nextcloud.com/install/#install-clients

## Issue Tracking

### Cross-Platform Labels
- **Mobile Issues:** `client: 🤖🍏 mobile`
- **Desktop Issues:** `client: 💻 desktop`

## Application Technologies

### Platform-Specific Development
- **Android App:** Java
- **iOS App:** Objective-C
- **Desktop Client:** C++ with Qt framework

## Contributing

### Diversity Ticket Contributions
Ways to contribute for diversity ticket eligibility:
- Solve a good first issue
- Work on the design
- Test Nextcloud and report issues
- Help with translations
- Organize a local Nextcloud meetup
- Other contributions to the community