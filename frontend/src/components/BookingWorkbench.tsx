"use client";

import { useEffect, useMemo, useState, type FormEvent } from "react";
import {
  ApiError,
  createBooking,
  fetchAvailableSeats,
  fetchTrips,
  type Booking,
  type SeatAvailability,
  type Trip,
} from "@/lib/api/client";
import { SeatMap } from "@/components/SeatMap";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";

type Status =
  | { kind: "idle" }
  | { kind: "loading" }
  | { kind: "error"; message: string }
  | { kind: "conflict"; message: string }
  | { kind: "success"; booking: Booking };

export function BookingWorkbench() {
  const [trips, setTrips] = useState<Trip[]>([]);
  const [tripsLoading, setTripsLoading] = useState(true);
  const [tripsError, setTripsError] = useState<string | null>(null);

  const [tripId, setTripId] = useState<number | null>(null);
  const [startStationId, setStartStationId] = useState<number | null>(null);
  const [endStationId, setEndStationId] = useState<number | null>(null);
  const [seats, setSeats] = useState<SeatAvailability[]>([]);
  const [seatsStatus, setSeatsStatus] = useState<Status>({ kind: "idle" });
  const [selectedSeatId, setSelectedSeatId] = useState<number | null>(null);
  const [customerName, setCustomerName] = useState("");
  const [customerEmail, setCustomerEmail] = useState("");
  const [bookingStatus, setBookingStatus] = useState<Status>({ kind: "idle" });

  const selectedTrip = useMemo(
    () => trips.find((trip) => trip.id === tripId) ?? null,
    [trips, tripId],
  );

  const orderedStations = selectedTrip?.stations ?? [];

  useEffect(() => {
    let cancelled = false;

    async function loadTrips() {
      setTripsLoading(true);
      setTripsError(null);
      try {
        const response = await fetchTrips();
        if (cancelled) return;
        setTrips(response.data);
        if (response.data[0]) {
          setTripId(response.data[0].id);
        }
      } catch (error) {
        if (cancelled) return;
        setTripsError(
          error instanceof Error
            ? error.message
            : "Unable to load trips from the API.",
        );
      } finally {
        if (!cancelled) setTripsLoading(false);
      }
    }

    void loadTrips();
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    setStartStationId(null);
    setEndStationId(null);
    setSeats([]);
    setSelectedSeatId(null);
    setSeatsStatus({ kind: "idle" });
    setBookingStatus({ kind: "idle" });
  }, [tripId]);

  async function loadSeats(
    nextTripId: number,
    nextStart: number,
    nextEnd: number,
  ) {
    setSeatsStatus({ kind: "loading" });
    setSelectedSeatId(null);
    try {
      const response = await fetchAvailableSeats(
        nextTripId,
        nextStart,
        nextEnd,
      );
      setSeats(response.data.seats);
      setSeatsStatus({ kind: "idle" });
    } catch (error) {
      setSeats([]);
      setSeatsStatus({
        kind: "error",
        message:
          error instanceof ApiError
            ? error.message
            : "Could not load seat availability.",
      });
    }
  }

  useEffect(() => {
    if (!tripId || !startStationId || !endStationId) {
      setSeats([]);
      return;
    }
    void loadSeats(tripId, startStationId, endStationId);
  }, [tripId, startStationId, endStationId]);

  async function handleBook(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!tripId || !startStationId || !endStationId || !selectedSeatId) {
      setBookingStatus({
        kind: "error",
        message: "Choose a trip segment and an available seat first.",
      });
      return;
    }

    setBookingStatus({ kind: "loading" });

    try {
      const response = await createBooking({
        trip_id: tripId,
        seat_id: selectedSeatId,
        start_station_id: startStationId,
        end_station_id: endStationId,
        customer_name: customerName.trim(),
        customer_email: customerEmail.trim(),
      });
      setBookingStatus({ kind: "success", booking: response.data });
      await loadSeats(tripId, startStationId, endStationId);
      setSelectedSeatId(null);
    } catch (error) {
      if (error instanceof ApiError && error.status === 409) {
        setBookingStatus({
          kind: "conflict",
          message:
            error.message ||
            "That seat was just taken. Availability has been refreshed.",
        });
        setSelectedSeatId(null);
        if (tripId && startStationId && endStationId) {
          await loadSeats(tripId, startStationId, endStationId);
        }
        return;
      }

      setBookingStatus({
        kind: "error",
        message:
          error instanceof Error ? error.message : "Booking failed unexpectedly.",
      });
    }
  }

  const endOptions = orderedStations.filter(
    (station) =>
      startStationId !== null &&
      station.sequence >
        (orderedStations.find((s) => s.id === startStationId)?.sequence ?? 0),
  );

  return (
    <div className="mx-auto flex w-full max-w-5xl flex-col gap-10 px-4 py-10 sm:px-6">
      <header className="space-y-3">
        <p className="font-display text-sm tracking-[0.2em] text-teal-800 uppercase">
          Golyv
        </p>
        <h1 className="font-display text-4xl leading-tight text-stone-900 sm:text-5xl">
          Fleet booking
        </h1>
        <p className="max-w-2xl text-base text-stone-600">
          Choose an Egypt route segment, pick an open seat, and reserve it.
          Availability always comes from the server.
        </p>
      </header>

      {tripsLoading && (
        <p className="text-sm text-stone-600" role="status">
          Loading trips…
        </p>
      )}

      {tripsError && (
        <Alert variant="destructive">
          <AlertTitle>Could not load trips</AlertTitle>
          <AlertDescription>{tripsError}</AlertDescription>
        </Alert>
      )}

      {!tripsLoading && !tripsError && trips.length === 0 && (
        <Alert>
          <AlertTitle>No trips yet</AlertTitle>
          <AlertDescription>
            Seed the backend database, then refresh this page.
          </AlertDescription>
        </Alert>
      )}

      {trips.length > 0 && (
        <section className="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
          <div className="space-y-6">
            <div className="space-y-2">
              <Label htmlFor="trip">Trip</Label>
              <select
                id="trip"
                className="h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                value={tripId ?? ""}
                onChange={(event) => setTripId(Number(event.target.value))}
              >
                {trips.map((trip) => (
                  <option key={trip.id} value={trip.id}>
                    {trip.name}
                  </option>
                ))}
              </select>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="start">Start station</Label>
                <select
                  id="start"
                  className="h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                  value={startStationId ?? ""}
                  onChange={(event) => {
                    const value = Number(event.target.value);
                    setStartStationId(value);
                    setEndStationId(null);
                  }}
                >
                  <option value="" disabled>
                    Select start
                  </option>
                  {orderedStations.slice(0, -1).map((station) => (
                    <option key={station.id} value={station.id}>
                      {station.name}
                    </option>
                  ))}
                </select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="end">End station</Label>
                <select
                  id="end"
                  className="h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                  value={endStationId ?? ""}
                  disabled={!startStationId}
                  onChange={(event) =>
                    setEndStationId(Number(event.target.value))
                  }
                >
                  <option value="" disabled>
                    Select end
                  </option>
                  {endOptions.map((station) => (
                    <option key={station.id} value={station.id}>
                      {station.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="space-y-3">
              <h2 className="font-display text-xl text-stone-900">Seats</h2>
              {seatsStatus.kind === "loading" && (
                <p className="text-sm text-stone-600" role="status">
                  Checking availability…
                </p>
              )}
              {seatsStatus.kind === "error" && (
                <Alert variant="destructive">
                  <AlertTitle>Seat lookup failed</AlertTitle>
                  <AlertDescription>{seatsStatus.message}</AlertDescription>
                </Alert>
              )}
              {!startStationId || !endStationId ? (
                <p className="text-sm text-stone-600">
                  Select start and end stations to see seats for that segment.
                </p>
              ) : (
                seatsStatus.kind !== "loading" &&
                seatsStatus.kind !== "error" && (
                  <SeatMap
                    seats={seats}
                    selectedSeatId={selectedSeatId}
                    onSelect={(seat) => setSelectedSeatId(seat.id)}
                    disabled={bookingStatus.kind === "loading"}
                  />
                )
              )}
            </div>
          </div>

          <form
            onSubmit={handleBook}
            className="space-y-4 rounded-2xl border border-teal-900/10 bg-white/80 p-6 shadow-[0_20px_60px_-40px_rgba(15,80,70,0.45)] backdrop-blur"
          >
            <h2 className="font-display text-2xl text-stone-900">
              Complete booking
            </h2>
            <p className="text-sm text-stone-600">
              {selectedSeatId
                ? `Seat ${seats.find((s) => s.id === selectedSeatId)?.number ?? ""} selected.`
                : "Select an available seat on the left."}
            </p>

            <div className="space-y-2">
              <Label htmlFor="name">Passenger name</Label>
              <Input
                id="name"
                required
                value={customerName}
                onChange={(event) => setCustomerName(event.target.value)}
                placeholder="Full name"
                autoComplete="name"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                required
                value={customerEmail}
                onChange={(event) => setCustomerEmail(event.target.value)}
                placeholder="you@example.com"
                autoComplete="email"
              />
            </div>

            <Button
              type="submit"
              className="w-full bg-teal-800 text-white hover:bg-teal-900"
              disabled={
                bookingStatus.kind === "loading" ||
                !selectedSeatId ||
                !customerName.trim() ||
                !customerEmail.trim()
              }
            >
              {bookingStatus.kind === "loading" ? "Booking…" : "Book seat"}
            </Button>

            {bookingStatus.kind === "conflict" && (
              <Alert variant="destructive">
                <AlertTitle>Seat no longer available</AlertTitle>
                <AlertDescription>{bookingStatus.message}</AlertDescription>
              </Alert>
            )}
            {bookingStatus.kind === "error" && (
              <Alert variant="destructive">
                <AlertTitle>Booking failed</AlertTitle>
                <AlertDescription>{bookingStatus.message}</AlertDescription>
              </Alert>
            )}
            {bookingStatus.kind === "success" && (
              <Alert className="border-teal-700/30 bg-teal-50 text-teal-950">
                <AlertTitle>Booking confirmed</AlertTitle>
                <AlertDescription>
                  Seat {bookingStatus.booking.seat.number} reserved from{" "}
                  {bookingStatus.booking.start_station.name} to{" "}
                  {bookingStatus.booking.end_station.name}. Confirmation #
                  {bookingStatus.booking.id}.
                </AlertDescription>
              </Alert>
            )}
          </form>
        </section>
      )}
    </div>
  );
}
