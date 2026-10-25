<script setup lang="ts">
defineProps<{ templates: { id: number; title: string; side: string; objective: string; map: string | null; summary: string | null }[]; modelValue: number | null }>();
const emit = defineEmits<{ 'update:modelValue': [id: number | null] }>();
</script>

<template>
    <fieldset>
        <legend class="text-sm text-muted">Template</legend>
        <label class="mt-2 flex items-start gap-2 text-sm"><input type="radio" name="template" :checked="modelValue === null" @change="emit('update:modelValue', null)" /> Blank plan</label>
        <label v-for="template in templates" :key="template.id" class="mt-2 flex items-start gap-2 text-sm">
            <input type="radio" name="template" :checked="modelValue === template.id" @change="emit('update:modelValue', template.id)" />
            <span><span class="font-display">{{ template.title }}</span><span class="block text-muted">{{ template.map }} · {{ template.side.toUpperCase() }} · example, not a verified lineup</span></span>
        </label>
    </fieldset>
</template>
