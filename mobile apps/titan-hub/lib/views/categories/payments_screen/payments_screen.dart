import '../../../../../utils/basic_screen_imports.dart';
import '../../../../../utils/responsive_layout.dart';
import 'payments_screen_mobile.dart';

class PaymentsScreen extends StatelessWidget {
  const PaymentsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: PaymentsScreenMobile(),
    );
  }
}
