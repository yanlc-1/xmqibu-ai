## MODIFIED Requirements
### Requirement: Durable and atomic publication
The system SHALL validate and deduplicate local bundles before committing them to MySQL; public pages SHALL read committed rows through a read-only account, and failed writes SHALL preserve the previous edition.

#### Scenario: Invalid bundle
- **WHEN** a local bundle lacks a verified source or valid date
- **THEN** reject it before changing database rows

#### Scenario: Repeat execution
- **WHEN** the same daily bundle is published again
- **THEN** no duplicate content appears

#### Scenario: Database unavailable
- **WHEN** MySQL is unavailable to a public page
- **THEN** respond with a temporary failure without exposing credentials

## ADDED Requirements
### Requirement: Versioned container deployment
The system SHALL build the web image from GitHub source, verify the new container, and keep a recoverable previous image.

#### Scenario: Failed release
- **WHEN** the candidate web container fails its health check
- **THEN** the old site remains available and the previous image can be restored
