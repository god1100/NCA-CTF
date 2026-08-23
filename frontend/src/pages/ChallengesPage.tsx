import { useState } from 'react'
import { useChallenges } from '../hooks/useChallenges'
import { ChallengeCard } from '../components/challenges/ChallengeCard'
import { ChallengeFiltersComponent } from '../components/challenges/ChallengeFilters'
import { Pagination } from '../components/ui/Pagination'
import { EmptyState } from '../components/ui/EmptyState'
import { ErrorState } from '../components/ui/ErrorState'
import { Skeleton } from '../components/ui/Skeleton'

export function ChallengesPage() {
  const { challenges, categories, pagination, isLoading, error, refetch } = useChallenges()
  const [selectedCategory, setSelectedCategory] = useState('')
  const [selectedDifficulty, setSelectedDifficulty] = useState('')
  const [searchQuery, setSearchQuery] = useState('')

  const filtered = challenges.filter(c => {
    const matchesSearch = !searchQuery || c.title.toLowerCase().includes(searchQuery.toLowerCase()) || c.description.toLowerCase().includes(searchQuery.toLowerCase())
    const matchesCategory = !selectedCategory || c.category === categories.find(cat => cat.slug === selectedCategory)?.name
    const matchesDifficulty = !selectedDifficulty || c.difficulty.toLowerCase() === selectedDifficulty
    return matchesSearch && matchesCategory && matchesDifficulty
  })

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-nca-text">Challenges</h1>
        <p className="text-sm text-nca-text-muted mt-1">Solve challenges to earn points and climb the leaderboard.</p>
      </div>

      <ChallengeFiltersComponent
        categories={categories}
        selectedCategory={selectedCategory}
        selectedDifficulty={selectedDifficulty}
        searchQuery={searchQuery}
        onCategoryChange={setSelectedCategory}
        onDifficultyChange={setSelectedDifficulty}
        onSearchChange={setSearchQuery}
      />

      {isLoading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="rounded-xl border border-nca-border bg-nca-surface p-5 space-y-3">
              <Skeleton className="h-5 w-3/4" />
              <Skeleton className="h-4 w-1/2" />
              <Skeleton className="h-16 w-full" />
            </div>
          ))}
        </div>
      ) : error ? (
        <ErrorState title="Failed to load challenges" description={error} onRetry={() => refetch()} />
      ) : filtered.length === 0 ? (
        <EmptyState title="No challenges found" description="Try adjusting your filters or search query." />
      ) : (
        <>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {filtered.map(challenge => (
              <ChallengeCard key={challenge.id} challenge={challenge} />
            ))}
          </div>
          {pagination && (
            <Pagination
              page={pagination.page}
              totalPages={pagination.total_pages}
              onPageChange={(page) => refetch({ page })}
              className="mt-6"
            />
          )}
        </>
      )}
    </div>
  )
}
