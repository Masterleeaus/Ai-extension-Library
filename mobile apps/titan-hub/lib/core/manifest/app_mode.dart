abstract final class AppMode {
  static const String service = 'service';
  static const String quoteFirst = 'quote_first';
  static const String booking = 'booking';
  static const String reservation = 'reservation';
  static const String capacityBooking = 'capacity_booking';
  static const String classMode = 'class';
  static const String eventTicketing = 'event_ticketing';
  static const String transportBooking = 'transport_booking';
  static const String accommodation = 'accommodation';
  static const String sales = 'sales';
  static const String orderAhead = 'order_ahead';
  static const String hire = 'hire';
  static const String rental = 'rental';
  static const String membership = 'membership';
  static const String subscription = 'subscription';
  static const String marketplace = 'marketplace';
  static const String application = 'application';

  static const Set<String> values = {
    service,
    quoteFirst,
    booking,
    reservation,
    capacityBooking,
    classMode,
    eventTicketing,
    transportBooking,
    accommodation,
    sales,
    orderAhead,
    hire,
    rental,
    membership,
    subscription,
    marketplace,
    application,
  };

  static String? normalize(String? value) {
    if (value == null) return null;

    final normalized = value.trim().toLowerCase().replaceAll('-', '_');
    if (values.contains(normalized)) return normalized;

    return switch (normalized) {
      'quote' => quoteFirst,
      'capacity' => capacityBooking,
      'ticketing' => eventTicketing,
      'transport' => transportBooking,
      'shop' || 'commerce' => sales,
      'subscribe' => subscription,
      _ => null,
    };
  }
}
