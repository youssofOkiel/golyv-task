import type { Metadata } from "next";
import { Fraunces, Source_Sans_3 } from "next/font/google";
import "./globals.css";

const display = Fraunces({
  subsets: ["latin"],
  variable: "--font-display",
});

const sans = Source_Sans_3({
  subsets: ["latin"],
  variable: "--font-sans",
});

export const metadata: Metadata = {
  title: "Golyv Fleet Booking",
  description:
    "Book bus seats across Egypt routes with segment-aware availability.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className={`${display.variable} ${sans.variable} h-full`}>
      <body className="min-h-full bg-[radial-gradient(circle_at_top_left,#d9f3ee,transparent_40%),linear-gradient(180deg,#f7f3ea_0%,#ebe4d6_100%)] font-sans text-stone-900 antialiased">
        {children}
      </body>
    </html>
  );
}
