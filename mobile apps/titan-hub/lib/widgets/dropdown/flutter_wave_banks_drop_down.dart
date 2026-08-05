// import 'package:google_fonts/google_fonts.dart';

// import '../../controller/categories/withdraw_controller/withdraw_controller.dart';
// import '../../language/language_controller.dart';
// import '../../utils/basic_screen_imports.dart';

// class FlutterWaveBanksDropDown extends StatefulWidget {
//   const FlutterWaveBanksDropDown({super.key});

//   @override
//   State<FlutterWaveBanksDropDown> createState() =>
//       _FlutterWaveBanksDropDownState();
// }

// class _FlutterWaveBanksDropDownState extends State<FlutterWaveBanksDropDown> {
//   final FocusNode focusNode = FocusNode();
//   final controller = Get.put(WithdrawController());

//   @override
//   void initState() {
//     super.initState();
//   }

//   @override
//   void dispose() {
//     focusNode.dispose();
//     super.dispose();
//   }

//   void _openBankSearch() {
//     showModalBottomSheet(
//       context: context,
//       isScrollControlled: true,
//       backgroundColor: Colors.transparent,
//       builder: (context) => DraggableScrollableSheet(
//         maxChildSize: 0.9,
//         initialChildSize: 0.7,
//         minChildSize: 0.7,
//         builder: (context, scrollController) => Container(
//           padding: EdgeInsets.all(Dimensions.paddingSize * 0.7),
//           decoration: BoxDecoration(
//             color: Colors.white,
//             borderRadius: BorderRadius.vertical(
//               top: Radius.circular(Dimensions.radius * 2),
//             ),
//           ),
//           child: Column(
//             crossAxisAlignment: CrossAxisAlignment.start,
//             children: [
//               Row(
//                 children: [
//                   const BackButton(),
//                   horizontalSpace(Dimensions.widthSize * 6),
//                   const TitleHeading3Widget(
//                     text: Strings.selectBank,
//                   ),
//                 ],
//               ),
//               PrimaryInputWidget(
//                 controller: controller.bankNameSearchController,
//                 hint: Get.find<LanguageController>()
//                     .getTranslation(Strings.search),
//                 label: '',
//                 prefixIcon: const Icon(Icons.search),
//                 onChanged: (value) {
//                   controller.filterTransaction(value);
//                 },
//                 radius: Dimensions.radius,
//               ),
//               verticalSpace(Dimensions.heightSize * 0.7),
//               Expanded(
//                 child: Obx(() {
//                   return ListView.builder(
//                     controller: scrollController,
//                     itemCount: controller.foundChapter.value.length,
//                     itemBuilder: (_, index) {
//                       var data = controller.foundChapter.value[index];
//                       return ListTile(
//                         title: TitleHeading4Widget(text: data.name),
//                         contentPadding: EdgeInsets.zero,
//                         onTap: () {
//                           controller.bankCode.value = data.code.toString();
//                           controller.bankNameController.text = data.name;
//                           controller.selectFlutterWaveBankId.value = data.id;
//                           controller.isSearchEnable.value = false;
//                           controller.getFlutterWaveBanksBranch();
//                           Navigator.pop(context);
//                         },
//                       );
//                     },
//                   );
//                 }),
//               ),
//             ],
//           ),
//         ),
//       ),
//     );
//   }

//   @override
//   Widget build(BuildContext context) {
//     return Column(
//       crossAxisAlignment: CrossAxisAlignment.start,
//       children: [
//         CustomTitleHeadingWidget(
//           text: Strings.bankName.tr,
//           style: CustomStyle.darkHeading4TextStyle.copyWith(
//             fontWeight: FontWeight.w600,
//             color: CustomColor.primaryTextColor,
//           ),
//         ),
//         SizedBox(height: Dimensions.heightSize * 0.7),
//         TextFormField(
//           controller: controller.bankNameController,
//           readOnly: true,
//           onTap: _openBankSearch,
//           decoration: InputDecoration(
//             hintText: Get.find<LanguageController>()
//                 .getTranslation(Strings.enterBankName),
//             hintStyle: GoogleFonts.inter(
//               fontSize: Dimensions.headingTextSize3,
//               fontWeight: FontWeight.w500,
//               color: CustomColor.primaryTextColor.withOpacity(0.2),
//             ),
//             border: OutlineInputBorder(
//               borderRadius: BorderRadius.circular(Dimensions.radius * 0.5),
//             ),
//             enabledBorder: OutlineInputBorder(
//               borderRadius: BorderRadius.circular(Dimensions.radius * 0.5),
//               borderSide: BorderSide(
//                 color: CustomColor.primaryLightColor.withOpacity(0.2),
//               ),
//             ),
//             focusedBorder: OutlineInputBorder(
//               borderRadius: BorderRadius.circular(Dimensions.radius * 0.5),
//               borderSide:
//                   BorderSide(width: 2, color: CustomColor.primaryLightColor),
//             ),
//             contentPadding: EdgeInsets.symmetric(
//               horizontal: Dimensions.widthSize * 1.7,
//               vertical: Dimensions.heightSize,
//             ),
//             suffixIcon: Icon(
//               Icons.arrow_drop_down,
//               size: Dimensions.iconSizeLarge,
//               color: CustomColor.primaryLightColor,
//             ),
//           ),
//         ),
//         verticalSpace(Dimensions.heightSize)
//       ],
//     );
//   }
// }
