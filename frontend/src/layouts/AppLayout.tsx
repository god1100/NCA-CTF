import { Outlet } from 'react-router-dom'
import { Sidebar } from '../components/layout/Sidebar'
import { TopBar } from '../components/layout/TopBar'
import { MobileDrawer } from '../components/layout/MobileDrawer'
import { useState } from 'react'

export function AppLayout() {
  const [mobileOpen, setMobileOpen] = useState(false)
  return (
    <div className="min-h-screen bg-nca-bg flex">
      <aside className="hidden lg:block w-64 fixed h-screen border-r border-nca-border bg-nca-surface">
        <Sidebar />
      </aside>
      <MobileDrawer isOpen={mobileOpen} onClose={() => setMobileOpen(false)} />
      <div className="flex-1 lg:ml-64 flex flex-col min-h-screen">
        <TopBar onMenuClick={() => setMobileOpen(true)} />
        <main className="flex-1 p-4 sm:p-6 lg:p-8"><Outlet /></main>
      </div>
    </div>
  )
}
