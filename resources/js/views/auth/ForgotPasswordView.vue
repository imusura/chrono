<script setup lang="ts">
import { ref } from 'vue'
import { authService } from '@/services/authService'
import { ValidationError } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'

const email = ref('')
const errors = ref<Record<string, string[]>>({})
const successMessage = ref('')
const isLoading = ref(false)

const submit = async () => {
  errors.value = {}
  successMessage.value = ''
  isLoading.value = true

  try {
    successMessage.value = await authService.forgotPassword(email.value)
  } catch (error) {
    if (error instanceof ValidationError) {
      errors.value = error.errors
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center px-4">
    <Card class="w-full max-w-sm">
      <CardHeader>
        <CardTitle class="text-2xl">Forgot Password</CardTitle>
        <CardDescription>Enter your email and we'll send you a reset link</CardDescription>
      </CardHeader>
      <CardContent>
        <Alert v-if="successMessage" class="mb-4">
          <AlertDescription>{{ successMessage }}</AlertDescription>
        </Alert>

        <form @submit.prevent="submit" class="grid gap-4">
          <div class="grid gap-2">
            <Label for="email">Email</Label>
            <Input
              id="email"
              v-model="email"
              type="email"
              placeholder="you@example.com"
              required
              autofocus
            />
            <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email[0] }}</p>
          </div>

          <Button type="submit" class="w-full" :disabled="isLoading">
            {{ isLoading ? 'Sending...' : 'Send Reset Link' }}
          </Button>

          <div class="text-center text-sm">
            <RouterLink :to="{ name: 'login' }" class="text-muted-foreground underline-offset-4 hover:underline">
              Back to login
            </RouterLink>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
