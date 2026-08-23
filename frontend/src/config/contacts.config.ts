export const CONTACTS_CONFIG = {
  organization: 'NCA Group',
  tagline: 'National Cybersecurity Academy — Batch 4',
  officialContact: {
    label: 'Official Contact',
    value: 'contact@example.com',
    href: 'mailto:contact@example.com',
  },
  community: {
    label: 'Discord Community',
    value: 'Coming Soon',
    href: null as string | null,
  },
  website: {
    label: 'Official Website',
    value: 'Coming Soon',
    href: null as string | null,
  },
  location: {
    label: 'Location',
    value: 'NCA Training Facility',
  },
}

export type ContactsConfig = typeof CONTACTS_CONFIG
