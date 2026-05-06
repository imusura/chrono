<script setup lang="ts">
import { ref, watch } from 'vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Skeleton } from '@/components/ui/skeleton'
import { Pencil, Trash2, Plus } from 'lucide-vue-next'
import { useOrganisations } from '@/composables/useOrganisations'
import type { Organisation } from '@/types'

const { query, storeMutation, updateMutation, destroyMutation } = useOrganisations()

const dialogOpen = ref(false)
const editingOrg = ref<Organisation | null>(null)
const name = ref('')
const nameError = ref('')

const deleteDialogOpen = ref(false)
const deletingOrg = ref<Organisation | null>(null)

const openCreate = () => {
  editingOrg.value = null
  name.value = ''
  nameError.value = ''
  dialogOpen.value = true
}

const openEdit = (org: Organisation) => {
  editingOrg.value = org
  name.value = org.name
  nameError.value = ''
  dialogOpen.value = true
}

const openDelete = (org: Organisation) => {
  deletingOrg.value = org
  deleteDialogOpen.value = true
}

watch(dialogOpen, (open) => {
  if (!open) nameError.value = ''
})

const submit = async () => {
  nameError.value = ''
  try {
    if (editingOrg.value) {
      await updateMutation.mutateAsync({ id: editingOrg.value.id, payload: { name: name.value } })
    } else {
      await storeMutation.mutateAsync({ name: name.value })
    }
    dialogOpen.value = false
  } catch (e: unknown) {
    const err = e as { errors?: Record<string, string[]> }
    if (err?.errors?.name) nameError.value = err.errors.name[0]
  }
}

const confirmDelete = async () => {
  if (!deletingOrg.value) return
  await destroyMutation.mutateAsync(deletingOrg.value.id)
  deleteDialogOpen.value = false
  deletingOrg.value = null
}

const formatDate = (str: string) =>
  new Date(str).toLocaleDateString('hr-HR', { day: 'numeric', month: 'long', year: 'numeric' })
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Organisations</h1>
      <Button @click="openCreate">
        <Plus class="h-4 w-4 mr-1" />
        New organisation
      </Button>
    </div>

    <div v-if="query.isLoading.value" class="space-y-2">
      <Skeleton v-for="i in 5" :key="i" class="h-14 w-full rounded-lg" />
    </div>

    <div v-else class="rounded-lg border divide-y">
      <div
        v-for="org in query.data.value"
        :key="org.id"
        class="flex items-center justify-between px-4 py-3"
      >
        <div>
          <p class="font-medium">{{ org.name }}</p>
          <p class="text-xs text-muted-foreground">Created {{ formatDate(org.created_at) }}</p>
        </div>
        <div class="flex gap-1">
          <Button variant="ghost" size="icon" @click="openEdit(org)">
            <Pencil class="h-4 w-4" />
          </Button>
          <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="openDelete(org)">
            <Trash2 class="h-4 w-4" />
          </Button>
        </div>
      </div>

      <div v-if="!query.data.value?.length" class="px-4 py-8 text-center text-sm text-muted-foreground">
        No organisations yet.
      </div>
    </div>

    <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>{{ editingOrg ? 'Edit organisation' : 'New organisation' }}</DialogTitle>
        </DialogHeader>
        <div class="grid gap-1.5 py-2">
          <Label for="name">Name</Label>
          <Input id="name" v-model="name" @keydown.enter="submit" />
          <p v-if="nameError" class="text-sm text-destructive">{{ nameError }}</p>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="dialogOpen = false">Cancel</Button>
          <Button :disabled="storeMutation.isPending.value || updateMutation.isPending.value" @click="submit">
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <AlertDialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete organisation?</AlertDialogTitle>
          <AlertDialogDescription>
            This will permanently delete <strong>{{ deletingOrg?.name }}</strong> and all its data. This cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            :disabled="destroyMutation.isPending.value"
            @click="confirmDelete"
          >
            Delete
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </AppLayout>
</template>
