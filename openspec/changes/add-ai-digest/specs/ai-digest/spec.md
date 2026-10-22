## ADDED Requirements
### Requirement: Evidence backed collection
The system SHALL record original URLs, original publication times, collection times and evidence for all rankings.
#### Scenario: Weekly repository ranking
- **WHEN** repositories are collected
- **THEN** sort verified weekly Star increases within the collected candidates and disclose coverage
#### Scenario: Daily news
- **WHEN** news is published in a daily edition
- **THEN** its source publication date MUST equal the edition date in Asia/Shanghai
### Requirement: Responsive reading
The system SHALL offer category, region and keyword filters, detailed Chinese introductions and original links on mobile and desktop.
#### Scenario: Small viewport
- **WHEN** viewport is 375 pixels wide
- **THEN** content remains readable without horizontal scrolling
### Requirement: Durable and atomic publication
The system SHALL validate bundles before MySQL storage and atomically switch static releases only after successful rendering.
#### Scenario: Invalid bundle
- **WHEN** an item lacks a verified source or valid date
- **THEN** reject the entire bundle and keep the published release intact
#### Scenario: Repeat execution
- **WHEN** the same daily bundle is published again
- **THEN** no duplicate content appears

### Requirement: AI news and cross-edition deduplication
The system SHALL collect at 12:00 Asia/Shanghai, restrict news to AI-centered events, and filter all previously published repository identities and news events before publication.
#### Scenario: Previously recommended repository
- **WHEN** a repository reappears with different capitalization, trailing slash, or a higher Star count
- **THEN** it is excluded from a different edition
#### Scenario: Syndicated news
- **WHEN** a news item shares a canonical source URL, normalized title, evidence link, or stable event key with a previous edition
- **THEN** it is filtered even if its primary link differs
#### Scenario: No new content
- **WHEN** all submitted items were previously published
- **THEN** no empty edition is created and the existing release remains active
#### Scenario: Same-day partial retry
- **WHEN** a same-day retry only includes some valid items
- **THEN** existing other items in that edition remain available
