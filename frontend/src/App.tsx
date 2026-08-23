import { useAuth } from './hooks/useAuth'
import { AppRoutes } from './routes'

export default function App() {
  const { isLoading } = useAuth()

  if (isLoading) {
    return (
      <div className="min-h-screen bg-nca-bg flex items-center justify-center">
        <div className="flex flex-col items-center gap-4">
          <div className="w-8 h-8 border-2 border-nca-accent border-t-transparent rounded-full animate-spin" />
          <p className="text-nca-text-muted text-sm font-mono">Initializing NCA-CTF...</p>
        </div>
      </div>
    )
  }

  return <AppRoutes />
}
