import { Card, CardContent } from '../ui/Card'
import { Button } from '../ui/Button'
import { Modal } from '../ui/Modal'
import { Alert } from '../ui/Alert'
import { LogOut, Trash2, AlertTriangle } from 'lucide-react'
import { useState } from 'react'

interface TeamActionsProps { isCaptain: boolean; onLeave: () => void }

export function TeamActions({ isCaptain, onLeave }: TeamActionsProps) {
  const [showLeaveModal, setShowLeaveModal] = useState(false)

  return (
    <>
      <Card>
        <CardContent className="py-4">
          <Button variant="danger" className="w-full" onClick={() => setShowLeaveModal(true)} leftIcon={<LogOut className="w-4 h-4" />}>
            Leave Team
          </Button>
          {isCaptain && (
            <p className="text-xs text-nca-text-dim mt-2">
              As captain, you must transfer captaincy before leaving.
            </p>
          )}
        </CardContent>
      </Card>
      <Modal isOpen={showLeaveModal} onClose={() => setShowLeaveModal(false)} title="Leave Team">
        <div className="space-y-4">
          <Alert variant="warning" title="Are you sure?">
            <div className="flex items-start gap-2">
              <AlertTriangle className="w-5 h-5 shrink-0" />
              <p>You will lose access to team challenges and scores. This action cannot be undone.</p>
            </div>
          </Alert>
          <div className="flex gap-3 justify-end">
            <Button variant="secondary" onClick={() => setShowLeaveModal(false)}>Cancel</Button>
            <Button variant="danger" onClick={() => { onLeave(); setShowLeaveModal(false); }} leftIcon={<Trash2 className="w-4 h-4" />}>Leave Team</Button>
          </div>
        </div>
      </Modal>
    </>
  )
}
