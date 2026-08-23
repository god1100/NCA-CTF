import { CONTACTS_CONFIG } from '../../config/contacts.config'
import { Mail, MessageCircle, Globe, MapPin } from 'lucide-react'

interface NCAContactsProps { compact?: boolean }

export function NCAContacts({ compact = false }: NCAContactsProps) {
  const items = [
    { icon: Mail, label: CONTACTS_CONFIG.officialContact.label, value: CONTACTS_CONFIG.officialContact.value, href: CONTACTS_CONFIG.officialContact.href },
    { icon: MessageCircle, label: CONTACTS_CONFIG.community.label, value: CONTACTS_CONFIG.community.value, href: CONTACTS_CONFIG.community.href },
    { icon: Globe, label: CONTACTS_CONFIG.website.label, value: CONTACTS_CONFIG.website.value, href: CONTACTS_CONFIG.website.href },
    { icon: MapPin, label: CONTACTS_CONFIG.location.label, value: CONTACTS_CONFIG.location.value, href: null },
  ]

  return (
    <div>
      {!compact && <h4 className="text-sm font-semibold text-nca-text mb-4">Contact</h4>}
      <ul className="space-y-3">
        {items.map(item => (
          <li key={item.label} className="flex items-start gap-3">
            <item.icon className="w-4 h-4 text-nca-text-dim mt-0.5 shrink-0" />
            <div>
              <p className="text-xs text-nca-text-dim">{item.label}</p>
              {item.href ? (
                <a href={item.href} className="text-sm text-nca-text-muted hover:text-nca-accent transition-colors">{item.value}</a>
              ) : (
                <p className="text-sm text-nca-text-muted">{item.value}</p>
              )}
            </div>
          </li>
        ))}
      </ul>
    </div>
  )
}
