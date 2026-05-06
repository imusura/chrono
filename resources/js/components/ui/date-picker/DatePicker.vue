<script setup lang="ts">
import { computed } from 'vue'
import type { DateValue } from 'reka-ui'
import { CalendarDate, getLocalTimeZone, today } from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { Calendar } from '@/components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

const props = defineProps<{
	modelValue?: string
	placeholder?: string
}>()

const emit = defineEmits<{
	'update:modelValue': [value: string]
}>()

const dateValue = computed<DateValue | undefined>(() => {
	if (!props.modelValue) return undefined
	const [y, m, d] = props.modelValue.split('-').map(Number)
	return new CalendarDate(y, m, d)
})

const displayText = computed(() => {
	if (!dateValue.value) return props.placeholder ?? 'Pick a date'
	const date = dateValue.value.toDate(getLocalTimeZone())
	return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
})

const handleSelect = (value: DateValue | undefined) => {
	if (!value) return
	const y = String(value.year).padStart(4, '0')
	const m = String(value.month).padStart(2, '0')
	const d = String(value.day).padStart(2, '0')
	emit('update:modelValue', `${y}-${m}-${d}`)
}
</script>

<template>
	<Popover>
		<PopoverTrigger as-child>
			<Button variant="outline" :class="cn('w-full justify-start text-left font-normal', !modelValue && 'text-muted-foreground')">
				<CalendarIcon class="mr-2 size-4" />
				{{ displayText }}
			</Button>
		</PopoverTrigger>
		<PopoverContent class="w-auto p-0" align="start">
			<Calendar :model-value="dateValue" initial-focus @update:model-value="handleSelect" />
		</PopoverContent>
	</Popover>
</template>
