abstract final class VerticalSlug {
  static const String fieldHomeServices = 'field-home-services';
  static const String accommodationRooming = 'accommodation-rooming';
  static const String realEstate = 'real-estate';
  static const String salonsPersonalCare = 'salons-personal-care';
  static const String fitnessMembership = 'fitness-membership';
  static const String automotiveServices = 'automotive-services';
  static const String ecommerceRetail = 'ecommerce-retail';
  static const String hireRental = 'hire-rental';
  static const String bookingCapacity = 'booking-capacity';

  static const Set<String> values = {
    fieldHomeServices,
    accommodationRooming,
    realEstate,
    salonsPersonalCare,
    fitnessMembership,
    automotiveServices,
    ecommerceRetail,
    hireRental,
    bookingCapacity,
  };

  static const Map<String, String> _aliases = {
    'field_home_services': fieldHomeServices,
    'field_service': fieldHomeServices,
    'bnb_hotel_rooming': accommodationRooming,
    'hotel_bnb': accommodationRooming,
    'rooming_house': accommodationRooming,
    'real_estate': realEstate,
    'real_estate_property': realEstate,
    'salons_personal_care': salonsPersonalCare,
    'salon_personal_care': salonsPersonalCare,
    'fitness_membership': fitnessMembership,
    'automotive_services': automotiveServices,
    'automotive': automotiveServices,
    'ecommerce_retail': ecommerceRetail,
    'hire_rental': hireRental,
    'booking_reservation_capacity': bookingCapacity,
    'booking_capacity': bookingCapacity,
  };

  static String? normalize(String? value) {
    if (value == null) return null;

    final normalized = value.trim().toLowerCase();
    if (values.contains(normalized)) return normalized;

    return _aliases[normalized];
  }
}
