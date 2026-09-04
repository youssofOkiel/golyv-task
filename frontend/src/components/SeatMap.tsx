"use client";

import { cn } from "@/lib/utils";
import type { SeatAvailability } from "@/lib/api/client";

type SeatMapProps = {
  seats: SeatAvailability[];
  selectedSeatId: number | null;
  onSelect: (seat: SeatAvailability) => void;
  disabled?: boolean;
};

export function SeatMap({
  seats,
  selectedSeatId,
  onSelect,
  disabled = false,
}: SeatMapProps) {
  if (seats.length === 0) {
    return (
      <p className="text-sm text-muted-foreground" role="status">
        No seats to display for this segment.
      </p>
    );
  }

  return (
    <div>
      <div
        className="mb-3 flex items-center gap-4 text-xs text-muted-foreground"
        aria-hidden
      >
        <span className="inline-flex items-center gap-1.5">
          <span className="size-3 rounded-sm bg-teal-700" /> Available
        </span>
        <span className="inline-flex items-center gap-1.5">
          <span className="size-3 rounded-sm bg-stone-300" /> Unavailable
        </span>
        <span className="inline-flex items-center gap-1.5">
          <span className="size-3 rounded-sm bg-amber-600" /> Selected
        </span>
      </div>
      <div
        className="grid grid-cols-4 gap-3 sm:grid-cols-6"
        role="listbox"
        aria-label="Bus seats"
      >
        {seats.map((seat) => {
          const selected = selectedSeatId === seat.id;
          const available = seat.available;

          return (
            <button
              key={seat.id}
              type="button"
              role="option"
              aria-selected={selected}
              aria-disabled={!available || disabled}
              disabled={!available || disabled}
              onClick={() => onSelect(seat)}
              className={cn(
                "flex h-14 flex-col items-center justify-center rounded-md border text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-700",
                available &&
                  !selected &&
                  "border-teal-800/30 bg-teal-50 text-teal-950 hover:bg-teal-100",
                available &&
                  selected &&
                  "border-amber-700 bg-amber-500 text-white shadow-sm",
                !available &&
                  "cursor-not-allowed border-stone-200 bg-stone-200 text-stone-500",
              )}
            >
              <span className="text-[10px] uppercase tracking-wide opacity-70">
                Seat
              </span>
              <span>{seat.number}</span>
            </button>
          );
        })}
      </div>
    </div>
  );
}
