import type { Metadata } from 'next'
import { Providers } from './providers'
import './globals.css'

export const metadata: Metadata = {
  title: 'نيوتري عراق',
  description: 'نظام إدارة مركز التغذية',
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="ar" dir="rtl">
      <body>
        <Providers>{children}</Providers>
      </body>
    </html>
  )
}
