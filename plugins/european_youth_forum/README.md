# Notes for European Youth Forum

## User group setup

The following user groups exist:
- INGYO Full member (OD) WITH Voting rights
- NYC Full member (OD) WITH Voting rights
- INGYO Full member (OD) NO Voting rights
- NYC Full member (OD) NO Voting rights
- INGYO Full member NOT participating
- NYC Full member NOT participating
- INGYO Observer (OD)
- NYC Observer (OD)
- INGYO Candidate (OD)
- NYC Candidate (OD)
- INGYO Substitute Delegate
- NYC Substitute Delegate
- Associates
- YFJ Board
- YFJ Staff
- Remote user

Plus for each Roll Call two groups - e.g. for Roll Call no. 3 the two groups "Voting 3: INGYO" and "Voting 3: NYC"

## Roll call setup

- Permissions:
  - All INGYO/NYC groups are added, not regarding voting rights
  - A hard-coded logic creates two pseudo groups "Full members INGYO" and "Full members NYC", grouping the full members with and without voting rights. The logic detects the relevant groups by searching for "Full member" and "NYC/"INGYO" in the name of the group.
  - For the quorum, only the full members (with or without voting rights) will be regarded

After the roll call:
  - Copy users of the present members of "INGYO Full member (OD) WITH Voting rights" will be copied into a voting group
  - Copy users of the present members of "NYC Full member (OD) WITH Voting rights" will be copied into a voting group

## Voting setup

- Permissions: Two user groups:
  - One with the title "Voting [number]: NYC" in the title (hard-coded logic), e.g. "Voting 7: NYC"
  - One with the title "Voting [number]: INGYO" in the title (hard-coded logic), e.g. "Voting 7: INGYO"
- Answer choices: Yes, No, Abstention
