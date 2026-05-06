<script setup lang="ts">
import { useRoute } from 'vue-router'
import { Clock, Building2, Users, Shield, Tag } from 'lucide-vue-next'
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarRail,
} from '@/components/ui/sidebar'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const auth = useAuthStore()
</script>

<template>
  <Sidebar collapsible="icon">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <RouterLink :to="{ name: 'time' }">
              <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-semibold">Chrono</span>
                <span class="truncate text-xs text-muted-foreground">Time Tracking</span>
              </div>
            </RouterLink>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <SidebarGroup>
        <SidebarGroupLabel>Navigation</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem>
              <SidebarMenuButton as-child :is-active="route.name === 'time'" :tooltip="'My Time'">
                <RouterLink :to="{ name: 'time' }">
                  <Clock />
                  <span>My Time</span>
                </RouterLink>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>

      <SidebarGroup v-if="auth.isAdmin || auth.isSuperAdmin">
        <SidebarGroupLabel>Admin</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-if="auth.isSuperAdmin">
              <SidebarMenuButton as-child :is-active="route.name === 'admin.organisations'" tooltip="Organisations">
                <RouterLink :to="{ name: 'admin.organisations' }">
                  <Building2 />
                  <span>Organisations</span>
                </RouterLink>
              </SidebarMenuButton>
            </SidebarMenuItem>
            <template v-if="auth.isAdmin">
              <SidebarMenuItem>
                <SidebarMenuButton as-child :is-active="route.name === 'admin.users'" tooltip="Users">
                  <RouterLink :to="{ name: 'admin.users' }">
                    <Users />
                    <span>Users</span>
                  </RouterLink>
                </SidebarMenuButton>
              </SidebarMenuItem>
              <SidebarMenuItem>
                <SidebarMenuButton as-child :is-active="route.name === 'admin.roles'" tooltip="Roles">
                  <RouterLink :to="{ name: 'admin.roles' }">
                    <Shield />
                    <span>Roles</span>
                  </RouterLink>
                </SidebarMenuButton>
              </SidebarMenuItem>
              <SidebarMenuItem>
                <SidebarMenuButton as-child :is-active="route.name === 'admin.activities'" tooltip="Activities">
                  <RouterLink :to="{ name: 'admin.activities' }">
                    <Tag />
                    <span>Activities</span>
                  </RouterLink>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </template>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>

    <SidebarRail />
  </Sidebar>
</template>
