import '../../../../../utils/basic_screen_imports.dart';
import '../../../../../utils/responsive_layout.dart';
import 'payment_log_screen_mobile.dart';

class PaymentLogScreen extends StatelessWidget {
  const PaymentLogScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: PaymentLogScreenMobile(),
    );
  }
}
