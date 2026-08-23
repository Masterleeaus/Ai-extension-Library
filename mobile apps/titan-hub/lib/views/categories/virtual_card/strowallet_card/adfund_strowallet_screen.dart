import '../../../../utils/basic_screen_imports.dart';
import '../../../../utils/responsive_layout.dart';
import '../../../../widgets/appbar/appbar_widget.dart';
import '../../../../widgets/inputs/strowallet_fund_keybaord_widget.dart';

class StrowalletAddFundScreen extends StatelessWidget {
  const StrowalletAddFundScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        appBar: const AppBarWidget(text: Strings.fund),
        body: _bodyWidget(context),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return ListView(
      physics: const BouncingScrollPhysics(),
      children: [
        StrowalletAddFundWidget(
          buttonText: Strings.proceed,
        ),
      ],
    );
  }
}
