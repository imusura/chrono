<script setup lang="ts">
defineProps<{
  class?: string
}>()
</script>

<template>
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" :class="$props.class">
		<!-- Shadow/back bubble -->
		<rect x="2" y="2" width="18" height="16" rx="4" fill="#f59e0b" />
		<!-- Front bubble -->
		<rect x="3" y="1" width="18" height="16" rx="4" fill="#f9a825" />
		<!-- Lighter overlay on right side -->
		<path d="M12 1H17C19.2 1 21 2.8 21 5V13C21 15.2 19.2 17 17 17H12V1Z" fill="#fbbf24" />
		<!-- Pointer/tail -->
		<path d="M8 17L10 22L13 17" fill="#f59e0b" />
		<!-- Question mark -->
		<path d="M12 5.5C10.5 5.5 9.5 6.5 9.5 7.8" stroke="white" stroke-width="2" stroke-linecap="round" fill="none" />
		<path d="M9.5 7.8C9.5 6.5 10.5 5.5 12 5.5C13.5 5.5 14.5 6.5 14.5 7.8C14.5 9 13.5 9.5 12 10.5V11.5" stroke="white" stroke-width="2" stroke-linecap="round" fill="none" />
		<!-- Dot -->
		<circle cx="12" cy="14" r="1.2" fill="white" />
	</svg>
</template>
