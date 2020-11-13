# EGP Notes

## Candidature motion type

Motion Sections:
- Title
- Nominated by (Type: Title; don't show as regular motion section; "Nominated" needs to be part of the title)
- Photo (Type: Image)
- Motivation Letter (Type: PDF Attachment)
- Curriculum Vitae (Type: PDF Attachment)
- Video (Type: embedded video)

An agenda needs to be configured by temporarily enabling the home page (disable the redirect):
- For each body, an agenda item to be created
- The agenda item has the motion type (candidacies) assigned
- The candidacies need to be tabled using a create-link that includes the agenda item ID

Candidature list is shown at /spring-2020/candidatures?agendaItemId=2

The back button override / addition to the breadcrumb menu on the candidature page is done by hard-coding the affected motion type IDs into the LayoutHooks.php of this plugin.
