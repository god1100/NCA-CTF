import { Select } from '../ui/Select'
import { Input } from '../ui/Input'
import type { Category } from '../../types'

interface ChallengeFiltersProps {
  categories: Category[]
  selectedCategory: string
  selectedDifficulty: string
  searchQuery: string
  onCategoryChange: (val: string) => void
  onDifficultyChange: (val: string) => void
  onSearchChange: (val: string) => void
}

export function ChallengeFiltersComponent({
  categories, selectedCategory, selectedDifficulty, searchQuery,
  onCategoryChange, onDifficultyChange, onSearchChange,
}: ChallengeFiltersProps) {
  return (
    <div className="flex flex-col sm:flex-row gap-3">
      <div className="flex-1">
        <Input
          type="text"
          placeholder="Search challenges..."
          value={searchQuery}
          onChange={e => onSearchChange(e.target.value)}
        />
      </div>
      <Select
        options={[{ value: '', label: 'All Categories' }, ...categories.map(c => ({ value: c.slug, label: c.name }))]}
        value={selectedCategory}
        onChange={e => onCategoryChange(e.target.value)}
      />
      <Select
        options={[
          { value: '', label: 'All Difficulties' },
          { value: 'easy', label: 'Easy' },
          { value: 'medium', label: 'Medium' },
          { value: 'hard', label: 'Hard' },
          { value: 'insane', label: 'Insane' },
        ]}
        value={selectedDifficulty}
        onChange={e => onDifficultyChange(e.target.value)}
      />
    </div>
  )
}
