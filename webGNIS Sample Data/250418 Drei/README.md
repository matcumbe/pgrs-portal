# Documentation for 250418 Drei Data

This file provides documentation for the additional columns added to the data files within the `250418 Drei` directory.

## Columns

### `status_tag`

This column indicates the status of the station mark based on observation or recovery information.

**Possible Values:**

*   `0`: No Tag (Default or status not determined)
*   `1`: Lost (Mark could not be found)
*   `2`: Disturbed (Mark found but likely moved or damaged)
*   `3`: Unrecovered (Status uncertain, requires further investigation)
*   `4`: Reobserved (Mark found and verified)

### `epoch`

This column represents the year of observation or establishment, extracted from the station name where available.

**Format:**

*   `YYYY.##`: Year with decimal part, indicating a specific survey epoch.
*   `YYYY`: Year only, when the exact epoch within the year is not specified in the name.

This value helps in understanding the temporal context of the station data, especially when analyzing changes over time.