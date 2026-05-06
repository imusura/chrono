<script setup lang="ts">
defineProps<{
  class?: string
}>()
</script>

<template>
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" :class="$props.class">
		<!-- Antennae -->
		<path d="M10 6L9 3.5" stroke="#475569" stroke-width="1.4" stroke-linecap="round" />
		<path d="M14 6L15 3.5" stroke="#475569" stroke-width="1.4" stroke-linecap="round" />
		<!-- Legs - top pair (curved outward, angled up) -->
		<path d="M7 11L5.1 10.2Q3.9 9.7 3.6 8.9" stroke="#475569" stroke-width="1.4" stroke-linecap="round" fill="none" />
		<path d="M17 11L18.9 10.2Q20.1 9.7 20.4 8.9" stroke="#475569" stroke-width="1.4" stroke-linecap="round" fill="none" />
		<!-- Legs - middle pair (curved outward, horizontal) -->
		<path d="M6.5 14L4.7 14.4Q3.6 14.4 3.3 13.6" stroke="#475569" stroke-width="1.4" stroke-linecap="round" fill="none" />
		<path d="M17.5 14L19.3 14.4Q20.4 14.4 20.7 13.6" stroke="#475569" stroke-width="1.4" stroke-linecap="round" fill="none" />
		<!-- Legs - bottom pair (curved outward, angled down) -->
		<path d="M7.5 17L5.6 17.8Q4.5 18.2 4.2 19" stroke="#475569" stroke-width="1.4" stroke-linecap="round" fill="none" />
		<path d="M16.5 17L18.4 17.8Q19.5 18.2 19.8 19" stroke="#475569" stroke-width="1.4" stroke-linecap="round" fill="none" />
		<!-- Head -->
		<ellipse cx="12" cy="7" rx="3" ry="2.5" fill="#475569" />
		<!-- Body background -->
		<ellipse cx="12" cy="14.5" rx="5.5" ry="6" fill="#ef4444" />
		<!-- Body - right half darker -->
		<path d="M12 8.5C14.8 8.5 17.5 11 17.5 14.5C17.5 18 14.8 20.5 12 20.5V8.5Z" fill="#dc2626" />
		<!-- Center line -->
		<line x1="12" y1="8.5" x2="12" y2="20.5" stroke="#475569" stroke-width="0.8" opacity="0.4" />
		<!-- Spots -->
		<circle cx="9.5" cy="12.5" r="0.9" fill="#991b1b" opacity="0.5" />
		<circle cx="14.5" cy="12.5" r="0.9" fill="#7f1d1d" opacity="0.5" />
		<circle cx="9.8" cy="16.5" r="0.9" fill="#991b1b" opacity="0.5" />
		<circle cx="14.2" cy="16.5" r="0.9" fill="#7f1d1d" opacity="0.5" />
	</svg>
</template>
