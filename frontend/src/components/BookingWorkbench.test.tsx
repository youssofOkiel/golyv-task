import { cleanup, render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { SeatMap } from "@/components/SeatMap";
import { BookingWorkbench } from "@/components/BookingWorkbench";
import * as api from "@/lib/api/client";

vi.mock("@/lib/api/client", async () => {
  const actual = await vi.importActual<typeof import("@/lib/api/client")>(
    "@/lib/api/client",
  );
  return {
    ...actual,
    fetchTrips: vi.fn(),
    fetchAvailableSeats: vi.fn(),
    createBooking: vi.fn(),
  };
});

const trip = {
  id: 1,
  name: "Cairo → Asyut",
  bus: { id: 1, name: "Bus", seat_count: 12 },
  stations: [
    { id: 1, name: "Cairo", code: "CAI", sequence: 1 },
    { id: 2, name: "Al Fayyum", code: "FAY", sequence: 2 },
    { id: 3, name: "Al Minya", code: "MIN", sequence: 3 },
    { id: 4, name: "Asyut", code: "ASY", sequence: 4 },
  ],
};

const seats = [
  { id: 1, number: 1, available: true },
  { id: 5, number: 5, available: false },
];

afterEach(() => {
  cleanup();
  vi.clearAllMocks();
});

describe("SeatMap", () => {
  it("renders available and unavailable seats", () => {
    render(
      <SeatMap seats={seats} selectedSeatId={null} onSelect={() => undefined} />,
    );

    expect(screen.getByRole("option", { name: /seat\s*1/i })).toBeEnabled();
    expect(screen.getByRole("option", { name: /seat\s*5/i })).toBeDisabled();
  });
});

describe("BookingWorkbench", () => {
  beforeEach(() => {
    vi.mocked(api.fetchTrips).mockResolvedValue({ data: [trip] });
    vi.mocked(api.fetchAvailableSeats).mockResolvedValue({
      data: {
        trip_id: 1,
        start_station_id: 1,
        end_station_id: 3,
        seats,
      },
    });
  });

  it("loads seats and completes a booking", async () => {
    const user = userEvent.setup();
    vi.mocked(api.createBooking).mockResolvedValue({
      data: {
        id: 10,
        trip_id: 1,
        seat: { id: 1, number: 1 },
        start_station: { id: 1, name: "Cairo", code: "CAI" },
        end_station: { id: 3, name: "Al Minya", code: "MIN" },
        start_sequence: 1,
        end_sequence: 3,
        customer_name: "Ada",
        customer_email: "ada@example.com",
        created_at: null,
      },
    });

    render(<BookingWorkbench />);

    expect(await screen.findByLabelText(/^trip$/i)).toBeInTheDocument();

    await user.selectOptions(screen.getByLabelText(/start station/i), "1");
    await user.selectOptions(screen.getByLabelText(/end station/i), "3");

    const seatMap = await screen.findByRole("listbox", { name: /bus seats/i });
    const seatOne = within(seatMap).getByRole("option", { name: /seat\s*1/i });
    expect(seatOne).toBeEnabled();
    expect(
      within(seatMap).getByRole("option", { name: /seat\s*5/i }),
    ).toBeDisabled();

    await user.click(seatOne);
    await user.type(screen.getByLabelText(/passenger name/i), "Ada");
    await user.type(screen.getByLabelText(/email/i), "ada@example.com");
    await user.click(screen.getByRole("button", { name: /book seat/i }));

    expect(await screen.findByText(/booking confirmed/i)).toBeInTheDocument();
  });

  it("shows API error when trips fail to load", async () => {
    vi.mocked(api.fetchTrips).mockRejectedValue(new Error("network down"));
    render(<BookingWorkbench />);
    expect(await screen.findByText(/could not load trips/i)).toBeInTheDocument();
    expect(screen.getByText(/network down/i)).toBeInTheDocument();
  });

  it("handles booking conflict by refreshing seats and clearing selection", async () => {
    const user = userEvent.setup();
    vi.mocked(api.createBooking).mockRejectedValue(
      new api.ApiError(409, {
        message:
          "The selected seat is no longer available for this trip segment.",
        error: "seat_unavailable",
      }),
    );

    render(<BookingWorkbench />);
    await screen.findByLabelText(/^trip$/i);
    await user.selectOptions(screen.getByLabelText(/start station/i), "1");
    await user.selectOptions(screen.getByLabelText(/end station/i), "3");

    const seatMap = await screen.findByRole("listbox", { name: /bus seats/i });
    await user.click(within(seatMap).getByRole("option", { name: /seat\s*1/i }));
    await user.type(screen.getByLabelText(/passenger name/i), "Ada");
    await user.type(screen.getByLabelText(/email/i), "ada@example.com");
    await user.click(screen.getByRole("button", { name: /book seat/i }));

    expect(
      await screen.findByText(/seat no longer available/i),
    ).toBeInTheDocument();
    await waitFor(() => {
      expect(api.fetchAvailableSeats).toHaveBeenCalledTimes(2);
    });
    expect(
      within(seatMap).getByRole("option", { name: /seat\s*1/i }),
    ).toHaveAttribute("aria-selected", "false");
  });

  it("shows loading state while seats are fetched", async () => {
    const user = userEvent.setup();
    let resolveSeats!: (value: api.AvailableSeatsResponse) => void;
    vi.mocked(api.fetchAvailableSeats).mockImplementation(
      () =>
        new Promise((resolve) => {
          resolveSeats = resolve;
        }),
    );

    render(<BookingWorkbench />);
    await screen.findByLabelText(/^trip$/i);
    await user.selectOptions(screen.getByLabelText(/start station/i), "1");
    await user.selectOptions(screen.getByLabelText(/end station/i), "3");

    expect(await screen.findByText(/checking availability/i)).toBeInTheDocument();

    resolveSeats({
      data: {
        trip_id: 1,
        start_station_id: 1,
        end_station_id: 3,
        seats,
      },
    });

    expect(
      await screen.findByRole("listbox", { name: /bus seats/i }),
    ).toBeInTheDocument();
  });
});
