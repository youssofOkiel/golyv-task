export type Station = {
  id: number;
  name: string;
  code: string;
  sequence: number;
};

export type Trip = {
  id: number;
  name: string;
  bus: {
    id: number;
    name: string;
    seat_count: number;
  };
  stations: Station[];
};

export type SeatAvailability = {
  id: number;
  number: number;
  available: boolean;
};

export type AvailableSeatsResponse = {
  data: {
    trip_id: number;
    start_station_id: number;
    end_station_id: number;
    seats: SeatAvailability[];
  };
};

export type Booking = {
  id: number;
  trip_id: number;
  seat: { id: number; number: number };
  start_station: { id: number; name: string; code: string };
  end_station: { id: number; name: string; code: string };
  start_sequence: number;
  end_sequence: number;
  customer_name: string;
  customer_email: string;
  created_at: string | null;
};

export type ApiErrorBody = {
  message?: string;
  error?: string;
  errors?: Record<string, string[]>;
};

export class ApiError extends Error {
  status: number;
  body: ApiErrorBody;

  constructor(status: number, body: ApiErrorBody) {
    super(body.message ?? `Request failed with status ${status}`);
    this.name = "ApiError";
    this.status = status;
    this.body = body;
  }
}

const API_URL =
  process.env.NEXT_PUBLIC_API_URL?.replace(/\/$/, "") ??
  "http://localhost:8000/api";

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(init?.headers ?? {}),
    },
    cache: "no-store",
  });

  const text = await response.text();
  const body = text ? (JSON.parse(text) as ApiErrorBody & T) : ({} as T);

  if (!response.ok) {
    throw new ApiError(response.status, body as ApiErrorBody);
  }

  return body as T;
}

export function fetchTrips(): Promise<{ data: Trip[] }> {
  return request("/trips");
}

export function fetchAvailableSeats(
  tripId: number,
  startStationId: number,
  endStationId: number,
): Promise<AvailableSeatsResponse> {
  const params = new URLSearchParams({
    start_station_id: String(startStationId),
    end_station_id: String(endStationId),
  });
  return request(`/trips/${tripId}/available-seats?${params.toString()}`);
}

export function createBooking(payload: {
  trip_id: number;
  seat_id: number;
  start_station_id: number;
  end_station_id: number;
  customer_name: string;
  customer_email: string;
}): Promise<{ data: Booking }> {
  return request("/bookings", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}
