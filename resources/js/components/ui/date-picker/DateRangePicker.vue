<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { DateRange } from 'reka-ui'
import { CalendarDate, getLocalTimeZone } from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { RangeCalendar } from '@/components/ui/range-calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'

const props = defineProps<{
	from?: string
	to?: string
	placeholder?: string
}>()

const emit = defineEmits<{
	'update:range': [value: { from: string; to: string }]
}>()

const toCalendarDate = (str?: string) => {
	if (!str) return undefined
	const [y, m, d] = str.split('-').map(Number)
	return new CalendarDate(y, m, d)
}

const toDateString = (date: CalendarDate) => {
	const y = String(date.year).padStart(4, '0')
	const m = String(date.month).padStart(2, '0')
	const d = String(date.day).padStart(2, '0')
	return `${y}-${m}-${d}`
}

const formatDisplay = (str?: string) => {
	if (!str) return null
	const date = toCalendarDate(str)
	if (!date) return null
	return date.toDate(getLocalTimeZone()).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const localRange = ref<DateRange>({
	start: toCalendarDate(props.from),
	end: toCalendarDate(props.to),
})

watch(() => [props.from, props.to], () => {
	localRange.value = {
		start: toCalendarDate(props.from),
		end: toCalendarDate(props.to),
	}
})

const displayText = computed(() => {
	const fromText = formatDisplay(props.from)
	const toText = formatDisplay(props.to)
	if (fromText && toText) return `${fromText} - ${toText}`
	if (fromText) return `${fromText} - ...`
	return props.placeholder ?? 'Pick a date range'
})

const hasValue = computed(() => !!props.from || !!props.to)

const handleUpdate = (value: DateRange) => {
	if (value.start && value.end) {
		localRange.value = value
		emit('update:range', {
			from: toDateString(value.start as CalendarDate),
			to: toDateString(value.end as CalendarDate),
		})
	}
}
</script>

<template>
	<Popover>
		<PopoverTrigger as-child>
			<Button variant="outline" :class="cn('w-auto justify-start text-left font-normal', !hasValue && 'text-muted-foreground')">
				<CalendarIcon class="mr-2 size-4" />
				<span class="truncate">{{ displayText }}</span>
			</Button>
		</PopoverTrigger>
		<PopoverContent class="w-auto p-0" align="start">
			<RangeCalendar :model-value="localRange" initial-focus @update:model-value="handleUpdate" />
		</PopoverContent>
	</Popover>
</template>
