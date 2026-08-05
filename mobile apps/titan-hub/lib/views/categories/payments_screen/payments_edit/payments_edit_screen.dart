import '../../../../../utils/basic_screen_imports.dart';
import '../../../../../utils/responsive_layout.dart';
import 'payments_edit_mobile_screen.dart';

class PaymentsEditScreen extends StatelessWidget {
  const PaymentsEditScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: PaymentsEditScreenMobile(),
    );
  }
}
