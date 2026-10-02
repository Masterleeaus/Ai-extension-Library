# Titan Commerce Booking Compatibility

New booking availability and customer reservations are part of **Titan Commerce** in `app/extensions/ChatbotEcommerce/`.

This legacy extension keeps its `chatbot-booking` installation identity and existing WorkCore bridge for backward compatibility. It is not the booking engine for new Titan Commerce deployments. Build new bookable offers from the shared seller catalogue and use Titan Commerce booking slots, capacity reservations and customer communications.

Existing installations can continue using their configured WorkCore integration while migration is planned. The compatibility package remains separate so existing extension keys and data are preserved.
