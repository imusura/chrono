<script setup lang="ts">
import { ref, watch } from 'vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
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
import { Badge } from '@/components/ui/badge'
import { Pencil, Trash2, Plus } from 'lucide-vue-next'
import { useAdminUsers } from '@/composables/useAdminUsers'
import type { AdminUser, StoreUserPayload, UpdateUserPayload } from '@/types'
import { ValidationError } from '@/types'

const { query, storeMutation, updateMutation, destroyMutation } = useAdminUsers()

const dialogOpen = ref(false)
const editingUser = ref<AdminUser | null>(null)

const name = ref('')
const email = ref('')
const password = ref('')
const contractedHours = ref(7.5)
const isAdmin = ref(false)
const fieldErrors = ref<Record<string, string[]>>({})

const deleteDialogOpen = ref(false)
const deletingUser = ref<AdminUser | null>(null)

const openCreate = () => {
  editingUser.value = null
  name.value = ''
  email.value = ''
  password.value = ''
  contractedHours.value = 7.5
  isAdmin.value = false
  fieldErrors.value = {}
  dialogOpen.value = true
}

const openEdit = (user: AdminUser) => {
  editingUser.value = user
  name.value = user.name
  email.value = user.email
  password.value = ''
  contractedHours.value = user.contracted_hours
  isAdmin.value = user.is_admin
  fieldErrors.value = {}
  dialogOpen.value = true
}

const openDelete = (user: AdminUser) => {
  deletingUser.value = user
  deleteDialogOpen.value = true
}

watch(dialogOpen, (open) => {
  if (!open) fieldErrors.value = {}
})

const submit = async () => {
  fieldErrors.value = {}
  try {
    if (editingUser.value) {
      const payload: UpdateUserPayload = {
        name: name.value,
        email: email.value,
        contracted_hours: contractedHours.value,
        is_admin: isAdmin.value,
      }
      if (password.value) payload.password = password.value
      await updateMutation.mutateAsync({ id: editingUser.value.id, payload })
    } else {
      const payload: StoreUserPayload = {
        name: name.value,
        email: email.value,
        password: password.value,
        contracted_hours: contractedHours.value,
        is_admin: isAdmin.value,
      }
      await storeMutation.mutateAsync(payload)
    }
    dialogOpen.value = false
  } catch (e) {
    if (e instanceof ValidationError) fieldErrors.value = e.errors
  }
}

const confirmDelete = async () => {
  if (!deletingUser.value) return
  await destroyMutation.mutateAsync(deletingUser.value.id)
  deleteDialogOpen.value = false
  deletingUser.value = null
}
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Users</h1>
      <Button @click="openCreate">
        <Plus class="h-4 w-4 mr-1" />
        New user
      </Button>
    </div>

    <div v-if="query.isLoading.value" class="space-y-2">
      <Skeleton v-for="i in 5" :key="i" class="h-14 w-full rounded-lg" />
    </div>

    <div v-else class="rounded-lg border divide-y">
      <div
        v-for="user in query.data.value"
        :key="user.id"
        class="flex items-center justify-between px-4 py-3"
      >
        <div>
          <div class="flex items-center gap-2">
            <p class="font-medium">{{ user.name }}</p>
            <Badge v-if="user.is_admin" variant="secondary">Admin</Badge>
          </div>
          <p class="text-xs text-muted-foreground">{{ user.email }} · {{ user.contracted_hours }}h/day</p>
        </div>
        <div class="flex gap-1">
          <Button variant="ghost" size="icon" @click="openEdit(user)">
            <Pencil class="h-4 w-4" />
          </Button>
          <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="openDelete(user)">
            <Trash2 class="h-4 w-4" />
          </Button>
        </div>
      </div>

      <div v-if="!query.data.value?.length" class="px-4 py-8 text-center text-sm text-muted-foreground">
        No users yet.
      </div>
    </div>

    <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{{ editingUser ? 'Edit user' : 'New user' }}</DialogTitle>
        </DialogHeader>

        <div class="grid gap-4 py-2">
          <div class="grid gap-1.5">
            <Label for="name">Name</Label>
            <Input id="name" v-model="name" />
            <p v-if="fieldErrors.name" class="text-sm text-destructive">{{ fieldErrors.name[0] }}</p>
          </div>
          <div class="grid gap-1.5">
            <Label for="email">Email</Label>
            <Input id="email" v-model="email" type="email" />
            <p v-if="fieldErrors.email" class="text-sm text-destructive">{{ fieldErrors.email[0] }}</p>
          </div>
          <div class="grid gap-1.5">
            <Label for="password">{{ editingUser ? 'New password (leave blank to keep)' : 'Password' }}</Label>
            <Input id="password" v-model="password" type="password" />
            <p v-if="fieldErrors.password" class="text-sm text-destructive">{{ fieldErrors.password[0] }}</p>
          </div>
          <div class="grid gap-1.5">
            <Label for="hours">Contracted hours per day</Label>
            <Input id="hours" v-model.number="contractedHours" type="number" step="0.5" min="0.5" max="24" />
            <p v-if="fieldErrors.contracted_hours" class="text-sm text-destructive">{{ fieldErrors.contracted_hours[0] }}</p>
          </div>
          <div class="flex items-center justify-between">
            <Label for="is_admin">Admin</Label>
            <Switch id="is_admin" :checked="isAdmin" @update:checked="isAdmin = $event" />
          </div>
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
          <AlertDialogTitle>Delete user?</AlertDialogTitle>
          <AlertDialogDescription>
            This will permanently delete <strong>{{ deletingUser?.name }}</strong>. This cannot be undone.
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
