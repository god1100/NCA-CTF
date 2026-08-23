import { useState, useEffect } from 'react'
import { challengeService } from '../../api/challenge.service'
import { Card, CardHeader, CardTitle, CardContent } from '../ui/Card'
import { Button } from '../ui/Button'
import { Spinner } from '../ui/Spinner'
import { EmptyState } from '../ui/EmptyState'
import { FileText, Download } from 'lucide-react'
import { formatBytes } from '../../utils/formatters'
import type { ChallengeFile } from '../../types'

export function ChallengeFiles({ challengeId }: { challengeId: number }) {
  const [files, setFiles] = useState<ChallengeFile[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    challengeService.files(challengeId).then(res => setFiles(res.files)).finally(() => setIsLoading(false))
  }, [challengeId])

  if (isLoading) return <Spinner size="sm" />
  if (files.length === 0) return <EmptyState title="No files" description="This challenge has no downloadable files." />

  return (
    <Card>
      <CardHeader><CardTitle className="text-base flex items-center gap-2"><FileText className="w-4 h-4" /> Files</CardTitle></CardHeader>
      <CardContent className="space-y-2">
        {files.map(file => (
          <div key={file.id} className="flex items-center justify-between p-3 rounded-lg bg-nca-bg border border-nca-border">
            <div className="flex items-center gap-3 min-w-0">
              <FileText className="w-4 h-4 text-nca-text-dim shrink-0" />
              <div className="min-w-0">
                <p className="text-sm font-medium text-nca-text truncate">{file.name}</p>
                <p className="text-xs text-nca-text-dim">{formatBytes(file.size)}</p>
              </div>
            </div>
            <a href={`/api/v1/challenge-files/${file.id}/download`} className="shrink-0">
              <Button size="sm" variant="secondary" leftIcon={<Download className="w-3.5 h-3.5" />}>Download</Button>
            </a>
          </div>
        ))}
      </CardContent>
    </Card>
  )
}
